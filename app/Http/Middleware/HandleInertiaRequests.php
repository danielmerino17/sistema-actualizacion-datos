<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $usuario = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                // Solo lo necesario para el frontend; no se manda el registro completo.
                'user' => $usuario instanceof Usuario ? [
                    'id' => $usuario->usua_id_usuario,
                    'nombre' => trim("{$usuario->usua_nombre} {$usuario->usua_apellido_paterno}"),
                    'rol' => $usuario->rol?->rol_nombre_rol,
                ] : null,
            ],
        ];
    }
}
