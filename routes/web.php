<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Profesor\DatosEmergenciaController;
use App\Http\Controllers\Profesor\DatosPersonalesController;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DesarrolloSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::inertia('/', 'Login')->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Profesor
|--------------------------------------------------------------------------
| Solo usuarios con sesión iniciada y rol Profesor.
*/
Route::middleware(['auth', 'rol:'.Rol::PROFESOR])->group(function () {
    Route::controller(DatosPersonalesController::class)
        ->prefix('datos-personales')
        ->name('datos-personales.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{contacto}', 'update')->whereNumber('contacto')->name('update');
            Route::delete('/{contacto}', 'destroy')->whereNumber('contacto')->name('destroy');
        });

    Route::controller(DatosEmergenciaController::class)
        ->prefix('datos-de-emergencia')
        ->name('datos-emergencia.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{persona}', 'update')->whereNumber('persona')->name('update');
            Route::delete('/{persona}', 'destroy')->whereNumber('persona')->name('destroy');
        });
});

/*
|--------------------------------------------------------------------------
| Solo en desarrollo (APP_ENV=local)
|--------------------------------------------------------------------------
| En el servidor estas rutas no se registran.
*/
if (app()->isLocal()) {
    // Muestra de componentes de la Parte 2.
    Route::inertia('/demo/componentes', 'Demo/Componentes')->name('demo.componentes');

    Route::post('/demo/flash', function (Request $request) {
        $datos = $request->validate([
            'tipo' => ['required', 'in:exito,error'],
            'mensaje' => ['required', 'string', 'max:120'],
        ]);

        return Inertia::flash($datos['tipo'], $datos['mensaje'])->back();
    })->name('demo.flash');

    // Acceso de prueba mientras no existe el login real (Parte 5).
    // Entra como el profesor inventado de DesarrolloSeeder.
    Route::get('/dev/entrar-como-profesor', function (Request $request) {
        $usuario = Usuario::where('usua_correo', DesarrolloSeeder::CORREO_PROFESOR_PRUEBA)->firstOrFail();

        Auth::login($usuario);
        $request->session()->regenerate();

        return to_route('datos-personales.index');
    })->name('dev.entrar-como-profesor');
}
