<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Login')->name('login');

/*
|--------------------------------------------------------------------------
| Muestra de componentes (solo en desarrollo)
|--------------------------------------------------------------------------
| Página para revisar los componentes de la Parte 2 contra el Figma.
| Solo existe cuando APP_ENV=local; en el servidor estas rutas no se registran.
*/
if (app()->isLocal()) {
    Route::inertia('/demo/componentes', 'Demo/Componentes')->name('demo.componentes');

    Route::post('/demo/flash', function (Request $request) {
        $datos = $request->validate([
            'tipo' => ['required', 'in:exito,error'],
            'mensaje' => ['required', 'string', 'max:120'],
        ]);

        return Inertia::flash($datos['tipo'], $datos['mensaje'])->back();
    })->name('demo.flash');
}
