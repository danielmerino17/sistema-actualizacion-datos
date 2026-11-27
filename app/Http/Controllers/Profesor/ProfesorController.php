<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use App\Models\PersonaContacto;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Base de las pantallas del profesor (Datos personales y Datos de emergencia).
 *
 * Todo se busca a partir del usuario con sesión iniciada, nunca por un id
 * que mande el navegador: así un profesor no puede ver datos de otro.
 */
abstract class ProfesorController extends Controller
{
    /** Cada cuántos meses se le pide al profesor revisar sus datos. */
    public const MESES_PARA_ACTUALIZAR = 6;

    protected function usuario(Request $request): Usuario
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return $usuario;
    }

    /**
     * La persona "titular" del profesor con sesión (sus propios datos).
     * Se crea en el login con los datos de bd_fca.
     */
    protected function personaTitular(Request $request): PersonaContacto
    {
        $persona = $this->usuario($request)->datosPersonales;

        abort_if($persona === null, 404, 'No hay datos personales registrados para este profesor.');

        return $persona;
    }

    /**
     * Datos comunes de las dos pantallas: el encabezado con los datos del
     * profesor y el aviso amarillo de actualización.
     *
     * @return array{profesor: array<string, string|null>, ultimaActualizacion: string|null, requiereActualizacion: bool}
     */
    protected function encabezado(PersonaContacto $titular): array
    {
        $ultimaActualizacion = $titular->peco_fecha_ultima_actualizacion;

        return [
            'profesor' => [
                'nombre' => $titular->peco_nombre,
                'apellidoPaterno' => $titular->peco_apellido_paterno,
                'apellidoMaterno' => $titular->peco_apellido_materno,
                'numeroTrabajador' => $titular->peco_numero_trabajador,
            ],
            'ultimaActualizacion' => $ultimaActualizacion?->format('d / m / Y'),
            'requiereActualizacion' => $ultimaActualizacion === null
                || $ultimaActualizacion->lt(now()->subMonths(self::MESES_PARA_ACTUALIZAR)),
        ];
    }
}
