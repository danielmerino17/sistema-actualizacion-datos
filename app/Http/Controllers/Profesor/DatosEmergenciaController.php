<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Requests\Profesor\GuardarContactoEmergenciaRequest;
use App\Models\Bitacora;
use App\Models\Contacto;
use App\Models\Parentesco;
use App\Models\PersonaContacto;
use App\Models\TipoContacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pantalla "Datos de emergencia" del profesor (Figma, flujos 3, 4 y 5).
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

        $contactos = $this->usuario($request)->contactosEmergencia()
            ->with(['parentesco', 'contactos'])
            ->orderBy('peco_id_persona_contacto')
            ->get();

        return Inertia::render('Profesor/DatosEmergencia', [
            ...$this->encabezado($titular),
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
            $persona = $this->usuario($request)->personas()->create([
                ...$request->datosPersona(),
                'peco_numero_trabajador' => $titular->peco_numero_trabajador,
            ]);

            $this->guardarTelefonos($persona, $request);
            $titular->save(); // actualiza la fecha de última actualización del profesor
            Bitacora::registrar($this->usuario($request), 'crear_emergencia');
        });

        return Inertia::flash('exito', 'Contacto de emergencia creado de manera exitosa')
            ->back();
    }

    public function update(GuardarContactoEmergenciaRequest $request, int $persona): RedirectResponse
    {
        $titular = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($request, $persona);

        DB::transaction(function () use ($request, $titular, $registro): void {
            $registro->update($request->datosPersona());
            $this->guardarTelefonos($registro, $request);
            $titular->save();
            Bitacora::registrar($this->usuario($request), 'editar_emergencia');
        });

        return Inertia::flash('exito', 'Contacto de emergencia actualizado de manera exitosa')
            ->back();
    }

    public function destroy(Request $request, int $persona): RedirectResponse
    {
        $titular = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($request, $persona);

        DB::transaction(function () use ($request, $titular, $registro): void {
            $registro->delete(); // sus teléfonos se borran en cascada
            $titular->save();
            Bitacora::registrar($this->usuario($request), 'borrar_emergencia');
        });

        return Inertia::flash('exito', 'Contacto de emergencia eliminado de manera exitosa')
            ->back();
    }

    /**
     * Busca el contacto de emergencia SOLO entre los del profesor con sesión.
     * Si es de otro profesor (o es su persona titular), responde 404.
     */
    private function contactoDelProfesor(Request $request, int $persona): PersonaContacto
    {
        return $this->usuario($request)->contactosEmergencia()->findOrFail($persona);
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
