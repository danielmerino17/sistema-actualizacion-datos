<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profesor\GuardarDatoPersonalRequest;
use App\Models\Bitacora;
use App\Models\Contacto;
use App\Models\PersonaContacto;
use App\Models\TipoContacto;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pantalla "Datos personales" del profesor: sus correos y teléfonos.
 *
 * Cada profesor solo puede ver y modificar SUS datos: todo se busca a
 * partir del usuario con sesión iniciada, nunca por un id que mande el navegador.
 */
class DatosPersonalesController extends Controller
{
    /** Cada cuántos meses se le pide al profesor revisar sus datos. */
    public const MESES_PARA_ACTUALIZAR = 6;

    public function index(Request $request): Response
    {
        $persona = $this->personaTitular($request);
        $persona->load(['contactos' => fn ($consulta) => $consulta->orderBy('cont_id_contacto'), 'contactos.tipo']);
        $ultimaActualizacion = $persona->peco_fecha_ultima_actualizacion;

        return Inertia::render('Profesor/DatosPersonales', [
            'profesor' => [
                'nombre' => $persona->peco_nombre,
                'apellidoPaterno' => $persona->peco_apellido_paterno,
                'apellidoMaterno' => $persona->peco_apellido_materno,
                'numeroTrabajador' => $persona->peco_numero_trabajador,
            ],
            'contactos' => $persona->contactos->map(fn (Contacto $contacto) => [
                'id' => $contacto->cont_id_contacto,
                'tipoId' => $contacto->cont_id_tipo_contacto,
                'medio' => $contacto->tipo->tico_tipo_contacto,
                'descripcion' => $contacto->cont_descripcion,
                'esPrincipal' => $contacto->cont_es_principal,
                'fecha' => $contacto->cont_fecha_actualizacion->format('d / m / Y'),
            ])->values(),
            'tiposContacto' => TipoContacto::orderBy('tico_id_tipo_contacto')->get()
                ->map(fn (TipoContacto $tipo) => [
                    'valor' => $tipo->tico_id_tipo_contacto,
                    'texto' => $tipo->tico_tipo_contacto,
                ])->values(),
            'ultimaActualizacion' => $ultimaActualizacion?->format('d / m / Y'),
            'requiereActualizacion' => $ultimaActualizacion === null
                || $ultimaActualizacion->lt(now()->subMonths(self::MESES_PARA_ACTUALIZAR)),
        ]);
    }

    public function store(GuardarDatoPersonalRequest $request): RedirectResponse
    {
        $persona = $this->personaTitular($request);
        $datos = $request->datosContacto();

        DB::transaction(function () use ($request, $persona, $datos): void {
            if ($datos['cont_es_principal']) {
                $this->quitarPrincipal($persona, $datos['cont_id_tipo_contacto']);
            }

            $persona->contactos()->create($datos);
            $persona->save(); // actualiza la fecha de última actualización
            Bitacora::registrar($this->usuario($request), 'crear_dato_personal');
        });

        return Inertia::flash('exito', 'Datos creados de manera exitosa')
            ->back();
    }

    public function update(GuardarDatoPersonalRequest $request, int $contacto): RedirectResponse
    {
        $persona = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($persona, $contacto);
        $datos = $request->datosContacto();

        DB::transaction(function () use ($request, $persona, $registro, $datos): void {
            if ($datos['cont_es_principal']) {
                $this->quitarPrincipal($persona, $datos['cont_id_tipo_contacto'], except: $registro->cont_id_contacto);
            }

            $registro->update($datos);
            $persona->save();
            Bitacora::registrar($this->usuario($request), 'editar_dato_personal');
        });

        return Inertia::flash('exito', 'Datos actualizados de manera exitosa')
            ->back();
    }

    public function destroy(Request $request, int $contacto): RedirectResponse
    {
        $persona = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($persona, $contacto);

        DB::transaction(function () use ($request, $persona, $registro): void {
            $registro->delete();
            $persona->save();
            Bitacora::registrar($this->usuario($request), 'borrar_dato_personal');
        });

        return Inertia::flash('exito', 'Datos eliminados de manera exitosa')
            ->back();
    }

    private function usuario(Request $request): Usuario
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return $usuario;
    }

    /**
     * La persona "titular" del profesor con sesión (sus propios datos).
     * Se crea al iniciar sesión (Parte 5) con los datos de bd_fca.
     */
    private function personaTitular(Request $request): PersonaContacto
    {
        $persona = $this->usuario($request)->datosPersonales;

        abort_if($persona === null, 404, 'No hay datos personales registrados para este profesor.');

        return $persona;
    }

    /**
     * Busca el contacto SOLO entre los del profesor; si es de otra persona, responde 404.
     */
    private function contactoDelProfesor(PersonaContacto $persona, int $contacto): Contacto
    {
        return $persona->contactos()->findOrFail($contacto);
    }

    /**
     * Solo puede haber un dato "Principal" por tipo de medio
     * (un correo principal, un celular principal, etc.).
     */
    private function quitarPrincipal(PersonaContacto $persona, int $tipoContacto, ?int $except = null): void
    {
        $persona->contactos()
            ->where('cont_id_tipo_contacto', $tipoContacto)
            ->when($except !== null, fn ($consulta) => $consulta->whereKeyNot($except))
            ->update(['cont_es_principal' => false]);
    }
}
