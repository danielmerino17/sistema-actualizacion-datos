<?php

use App\Models\Bitacora;
use App\Models\PersonaContacto;
use App\Models\Rol;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Nombrar o quitar administradores (no hay pantalla para esto en el Figma).
| El usuario debe haber iniciado sesión al menos una vez.
|
|   php artisan sacpa:admin 1234567            → lo hace Administrador
|   php artisan sacpa:admin 1234567 --quitar   → lo regresa a Profesor
*/
Artisan::command('sacpa:admin {numero_trabajador} {--quitar : Regresarlo a Profesor}', function (string $numero_trabajador) {
    $persona = PersonaContacto::titular()->delProfesor(trim($numero_trabajador))->with('usuario')->first();

    if ($persona?->usuario === null) {
        $this->error("No hay ningún usuario con el número de trabajador {$numero_trabajador}. Debe iniciar sesión al menos una vez.");

        return 1;
    }

    $rol = $this->option('quitar') ? Rol::PROFESOR : Rol::ADMINISTRADOR;
    $usuario = $persona->usuario;

    $usuario->update(['usua_id_rol' => Rol::where('rol_nombre_rol', $rol)->value('rol_id_rol')]);
    Bitacora::registrar($usuario, $this->option('quitar') ? 'quitar_admin' : 'nombrar_admin');

    $this->info("{$usuario->usua_nombre} {$usuario->usua_apellido_paterno} ({$numero_trabajador}) ahora tiene el rol {$rol}.");

    return 0;
})->purpose('Nombra (o quita con --quitar) a un administrador de SACPA por su número de trabajador');
