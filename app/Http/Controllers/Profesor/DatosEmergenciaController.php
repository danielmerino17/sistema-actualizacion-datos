<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Requests\Profesor\GuardarContactoEmergenciaRequest;
use App\Models\Contacto;
use App\Models\Parentesco;
use App\Models\PersonaContacto;
use App\Models\TipoContacto;
use App\Support\ExportadorExcel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pantalla "Datos de emergencia" (Figma, flujos 3, 4 y 5).
 * La usan el profesor (sus datos) y el administrador (los de un profesor);
 * ver ProfesorController.
 *
 * Cada contacto de emergencia es una persona_contacto con parentesco que
 * pertenece al profesor. Sus teléfonos se guardan en la tabla contacto:
 *  - Teléfono (celular): dato principal, tipo "Celular".
 *  - Teléfono (alterno): dato secundario, tipo "Casa" (opcional).
 */
class DatosEmergenciaController extends ProfesorController
{
    public function index(Request $request): Response
    {
        $titular = $this->personaTitular($request);

        $contactos = $this->contactosDeEmergencia($request);

        // Que un administrador vea los datos de un profesor queda registrado.
        if ($this->vistaAdministrador($request)) {
            $this->registrarEnBitacora($request, 'consulta_emergencia');
        }

        return Inertia::render('Profesor/DatosEmergencia', [
            ...$this->encabezado($request, $titular),
            'contactos' => $contactos->values()->map(fn (PersonaContacto $persona, int $indice) => [
                'id' => $persona->peco_id_persona_contacto,
                'numero' => $indice + 1,
                'nombre' => $persona->peco_nombre,
                'apellidoPaterno' => $persona->peco_apellido_paterno,
                'apellidoMaterno' => $persona->peco_apellido_materno,
                'parentescoId' => $persona->peco_id_parentesco,
                'parentesco' => $persona->parentesco?->pare_parentesco,
                'celular' => $this->telefono($persona, principal: true),
                'alterno' => $this->telefono($persona, principal: false),
                'fecha' => $persona->peco_fecha_ultima_actualizacion?->format('d/m/Y'),
            ]),
            'parentescos' => Parentesco::orderBy('pare_id_parentesco')->get()
                ->map(fn (Parentesco $parentesco) => [
                    'valor' => $parentesco->pare_id_parentesco,
                    'texto' => $parentesco->pare_parentesco,
                ])->values(),
        ]);
    }

    public function store(GuardarContactoEmergenciaRequest $request): RedirectResponse
    {
        $titular = $this->personaTitular($request);

        DB::transaction(function () use ($request, $titular): void {
            $persona = $this->profesor($request)->personas()->create([
                ...$request->datosPersona(),
                'peco_numero_trabajador' => $titular->peco_numero_trabajador,
            ]);

            $this->guardarTelefonos($persona, $request);
            $titular->save(); // actualiza la fecha de última actualización del profesor
            $this->registrarEnBitacora($request, 'crear_emergencia', "contacto de emergencia #{$persona->peco_id_persona_contacto}");
        });

        return Inertia::flash('exito', 'Contacto de emergencia creado de manera exitosa')
            ->back();
    }

    public function update(GuardarContactoEmergenciaRequest $request): RedirectResponse
    {
        $titular = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($request);

        DB::transaction(function () use ($request, $titular, $registro): void {
            $registro->update($request->datosPersona());
            $this->guardarTelefonos($registro, $request);
            $titular->save();
            $this->registrarEnBitacora($request, 'editar_emergencia', "contacto de emergencia #{$registro->peco_id_persona_contacto}");
        });

        return Inertia::flash('exito', 'Contacto de emergencia actualizado de manera exitosa')
            ->back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $titular = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($request);

        DB::transaction(function () use ($request, $titular, $registro): void {
            $registro->delete(); // sus teléfonos se borran en cascada
            $titular->save();
            $this->registrarEnBitacora($request, 'borrar_emergencia', "contacto de emergencia #{$registro->peco_id_persona_contacto}");
        });

        return Inertia::flash('exito', 'Contacto de emergencia eliminado de manera exitosa')
            ->back();
    }

    /**
     * Descarga en Excel (solo administrador; ver rutas).
     */
    public function excel(Request $request, ExportadorExcel $exportador): BinaryFileResponse
    {
        $titular = $this->personaTitular($request);

        $this->registrarEnBitacora($request, 'excel_emergencia');

        return $exportador->descargar(
            prefijo: 'DatosEmergencia',
            titulo: 'Datos de emergencia',
            profesor: $titular,
            columnas: [
                'No. Contacto', 'Nombre(s)', 'Apellido paterno', 'Apellido materno', 'Parentesco',
                'Teléfono (celular)', 'Teléfono (alterno)', 'Última fecha de actualización',
            ],
            filas: $this->contactosDeEmergencia($request)->values()->map(fn (PersonaContacto $persona, int $indice) => [
                $indice + 1,
                $persona->peco_nombre,
                $persona->peco_apellido_paterno,
                $persona->peco_apellido_materno,
                $persona->parentesco?->pare_parentesco,
                $this->telefono($persona, principal: true),
                $this->telefono($persona, principal: false),
                $persona->peco_fecha_ultima_actualizacion?->format('d/m/Y'),
            ]),
        );
    }

    /**
     * Contactos de emergencia del profesor objetivo, con parentesco y teléfonos.
     *
     * @return Collection<int, PersonaContacto>
     */
    private function contactosDeEmergencia(Request $request): Collection
    {
        return $this->profesor($request)->contactosEmergencia()
            ->with(['parentesco', 'contactos'])
            ->orderBy('peco_id_persona_contacto')
            ->get();
    }

    /**
     * Busca el contacto de emergencia SOLO entre los del profesor objetivo.
     * Si es de otro profesor (o es su persona titular), responde 404.
     */
    private function contactoDelProfesor(Request $request): PersonaContacto
    {
        return $this->profesor($request)->contactosEmergencia()->findOrFail($this->idDeRuta($request, 'persona'));
    }

    private function telefono(PersonaContacto $persona, bool $principal): ?string
    {
        return $persona->contactos
            ->first(fn (Contacto $contacto) => $contacto->cont_es_principal === $principal)
            ?->cont_descripcion;
    }

    /**
     * Crea o actualiza el celular (principal) y el alterno (secundario).
     * Si el alterno se deja vacío, se elimina.
     */
    private function guardarTelefonos(PersonaContacto $persona, GuardarContactoEmergenciaRequest $request): void
    {
        $persona->contactos()->updateOrCreate(
            ['cont_es_principal' => true],
            [
                'cont_id_tipo_contacto' => $this->tipoContacto('Celular'),
                'cont_descripcion' => (string) $request->input('telefono_celular'),
            ],
        );

        $alterno = $request->input('telefono_alterno');

        if ($alterno === null) {
            $persona->contactos()->where('cont_es_principal', false)->delete();

            return;
        }

        $persona->contactos()->updateOrCreate(
            ['cont_es_principal' => false],
            [
                'cont_id_tipo_contacto' => $this->tipoContacto('Casa'),
                'cont_descripcion' => (string) $alterno,
            ],
        );
    }

    private function tipoContacto(string $nombre): int
    {
        return (int) TipoContacto::where('tico_tipo_contacto', $nombre)->valueOrFail('tico_id_tipo_contacto');
    }
}
