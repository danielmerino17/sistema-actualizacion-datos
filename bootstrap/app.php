<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\VerificarRol;
use App\Models\Usuario;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'rol' => VerificarRol::class,
        ]);

        // Si alguien con sesión abre el login (/), se le manda a su pantalla
        // según su rol. Sin esto Laravel redirige a "/" y se forma un ciclo infinito.
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user() instanceof Usuario
                ? $request->user()->rutaInicio()
                : route('datos-personales.index'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Más de 5 intentos de login por minuto: mensaje en el formulario,
        // en lugar de la página de error "Too Many Requests" en inglés.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->routeIs('login.store')) {
                return null;
            }

            $segundos = (int) ($e->getHeaders()['Retry-After'] ?? 60);

            return back()
                ->withInput($request->only('numero_trabajador'))
                ->withErrors([
                    'numero_trabajador' => "Demasiados intentos. Espera {$segundos} segundos e intenta de nuevo.",
                ]);
        });
    })->create();
