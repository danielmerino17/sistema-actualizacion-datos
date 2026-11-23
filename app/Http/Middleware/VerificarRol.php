<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a usuarios activos con alguno de los roles indicados.
 *
 * Uso en rutas: ->middleware('rol:Profesor') o ->middleware('rol:Administrador,Profesor')
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        abort_unless(
            $usuario instanceof Usuario && $usuario->usua_activo && $usuario->tieneRol(...$roles),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
