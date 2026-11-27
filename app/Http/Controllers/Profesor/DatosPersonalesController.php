<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Requests\Profesor\GuardarDatoPersonalRequest;
use App\Models\Bitacora;
use App\Models\Contacto;
use App\Models\PersonaContacto;
use App\Models\TipoContacto;
use App\Support\ExportadorExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pantalla "Datos personales": correos y teléfonos del profesor.
 * La usan el profesor (sus datos) y el administrador (los de un profesor);
 * ver ProfesorController.
 */
class DatosPersonalesController extends ProfesorController
{
    public function index(Request $request): Response
    {
        $persona = $this->personaTitular($request);
        $persona->load(['contactos' => fn ($consulta) => $consulta->orderBy('cont_id_contacto'), 'contactos.tipo']);

        return Inertia::render('Profesor/DatosPersonales', [
            ...$this->encabezado($request, $persona),
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

    public function update(GuardarDatoPersonalRequest $request): RedirectResponse
    {
        $persona = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($persona, $this->idDeRuta($request, 'contacto'));
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

    public function destroy(Request $request): RedirectResponse
    {
        $persona = $this->personaTitular($request);
        $registro = $this->contactoDelProfesor($persona, $this->idDeRuta($request, 'contacto'));

        DB::transaction(function () use ($request, $persona, $registro): void {
            $registro->delete();
            $persona->save();
            Bitacora::registrar($this->usuario($request), 'borrar_dato_personal');
        });

        return Inertia::flash('exito', 'Datos eliminados de manera exitosa')
            ->back();
    }

    /**
     * Descarga en Excel (solo administrador; ver rutas).
     */
    public function excel(Request $request, ExportadorExcel $exportador): BinaryFileResponse
    {
        $persona = $this->personaTitular($request);
        $persona->load(['contactos' => fn ($consulta) => $consulta->orderBy('cont_id_contacto'), 'contactos.tipo']);

        Bitacora::registrar($this->usuario($request), 'excel_datos_person');

        return $exportador->descargar(
            prefijo: 'DatosPersonales',
            titulo: 'Datos personales',
            profesor: $persona,
            columnas: ['Medio', 'Datos de contacto', 'Tipo de uso', 'Última fecha de actualización'],
            filas: $persona->contactos->map(fn (Contacto $contacto) => [
                $contacto->tipo->tico_tipo_contacto,
                $contacto->cont_descripcion,
                $contacto->cont_es_principal ? 'Principal' : 'Secundario',
                $contacto->cont_fecha_actualizacion->format('d/m/Y'),
            ]),
        );
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
