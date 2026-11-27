<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Base de las pantallas Datos personales y Datos de emergencia.
 *
 * Se usan desde dos lugares:
 *  - /datos-personales, /datos-de-emergencia            → el PROFESOR con sesión ve sus datos.
 *  - /admin/profesores/{profesor}/datos-personales, ... → el ADMINISTRADOR ve los de un profesor.
 *
 * El "profesor objetivo" sale del usuario con sesión o, en las rutas de
 * administrador (protegidas con rol:Administrador), del {profesor} de la URL.
 * Así un profesor nunca puede ver datos de otro.
 */
abstract class ProfesorController extends Controller
{
    /** Cada cuántos meses se le pide al profesor revisar sus datos. */
    public const MESES_PARA_ACTUALIZAR = 6;

    /** El usuario con sesión iniciada (quien hace la acción; va a la bitácora). */
    protected function usuario(Request $request): Usuario
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        return $usuario;
    }

    /** ¿La petición viene de las rutas de administrador? */
    protected function vistaAdministrador(Request $request): bool
    {
        return $request->route('profesor') !== null;
    }

    /**
     * El profesor cuyos datos se ven o se modifican.
     */
    protected function profesor(Request $request): Usuario
    {
        if (! $this->vistaAdministrador($request)) {
            return $this->usuario($request);
        }

        return Usuario::whereKey((int) $request->route('profesor'))
            ->whereHas('rol', fn ($consulta) => $consulta->where('rol_nombre_rol', Rol::PROFESOR))
            ->firstOrFail();
    }

    /**
     * La persona "titular" del profesor objetivo (sus propios datos).
     * Se crea en el login con los datos de bd_fca.
     */
    protected function personaTitular(Request $request): PersonaContacto
    {
        $persona = $this->profesor($request)->datosPersonales;

        abort_if($persona === null, 404, 'No hay datos personales registrados para este profesor.');

        return $persona;
    }

    /**
     * Inicio de las URLs de la pantalla: '' para el profesor,
     * '/admin/profesores/{id}' para el administrador.
     */
    protected function rutaProfesor(Request $request): string
    {
        return $this->vistaAdministrador($request)
            ? '/admin/profesores/'.(int) $request->route('profesor')
            : '';
    }

    /**
     * Datos comunes de las dos pantallas: el encabezado con los datos del
     * profesor, el aviso amarillo de actualización y desde dónde se ve.
     *
     * @return array{profesor: array<string, string|null>, ultimaActualizacion: string|null, requiereActualizacion: bool, rutaProfesor: string, esAdministrador: bool}
     */
    protected function encabezado(Request $request, PersonaContacto $titular): array
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
            'rutaProfesor' => $this->rutaProfesor($request),
            'esAdministrador' => $this->vistaAdministrador($request),
        ];
    }

    /**
     * Registra en la bitácora quién hizo la acción (usuario con sesión),
     * a qué profesor afectó y qué registro se tocó.
     */
    protected function registrarEnBitacora(Request $request, string $accion, ?string $detalle = null): void
    {
        Bitacora::registrar($this->usuario($request), $accion, $this->profesor($request), $detalle);
    }

    /**
     * Id que viene en la URL (ej. {contacto} o {persona}).
     */
    protected function idDeRuta(Request $request, string $nombre): int
    {
        return (int) $request->route($nombre);
    }
}
