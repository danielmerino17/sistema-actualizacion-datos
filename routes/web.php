<?php

use App\Http\Controllers\Admin\BuscarProfesorController;
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
| Pantallas "Datos personales" y "Datos de emergencia".
| Se registran dos veces: para el profesor (sus datos) y para el
| administrador (los datos de un profesor, bajo /admin/profesores/{profesor}).
*/
$rutasDatosProfesor = function (): void {
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
};

/*
|--------------------------------------------------------------------------
| Profesor
|--------------------------------------------------------------------------
| Solo usuarios con sesión iniciada y rol Profesor.
*/
Route::middleware(['auth', 'rol:'.Rol::PROFESOR])->group($rutasDatosProfesor);

/*
|--------------------------------------------------------------------------
| Administrador
|--------------------------------------------------------------------------
| Solo usuarios con sesión iniciada y rol Administrador.
*/
Route::middleware(['auth', 'rol:'.Rol::ADMINISTRADOR])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () use ($rutasDatosProfesor) {
        Route::get('/', BuscarProfesorController::class)->name('buscar');

        Route::prefix('profesores/{profesor}')
            ->whereNumber('profesor')
            ->name('profesores.')
            ->group(function () use ($rutasDatosProfesor) {
                $rutasDatosProfesor();

                Route::get('datos-personales/excel', [DatosPersonalesController::class, 'excel'])
                    ->name('datos-personales.excel');
                Route::get('datos-de-emergencia/excel', [DatosEmergenciaController::class, 'excel'])
                    ->name('datos-emergencia.excel');
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

    // Accesos de prueba para quien no tiene bd_fca en su máquina.
    // Entran como los usuarios inventados de DesarrolloSeeder.
    $entrarComo = function (string $correo) {
        return function (Request $request) use ($correo) {
            $usuario = Usuario::where('usua_correo', $correo)->firstOrFail();

            Auth::login($usuario);
            $request->session()->regenerate();

            return redirect()->to($usuario->rutaInicio());
        };
    };

    Route::get('/dev/entrar-como-profesor', $entrarComo(DesarrolloSeeder::CORREO_PROFESOR_PRUEBA))
        ->name('dev.entrar-como-profesor');
    Route::get('/dev/entrar-como-admin', $entrarComo(DesarrolloSeeder::CORREO_ADMIN_PRUEBA))
        ->name('dev.entrar-como-admin');
}
