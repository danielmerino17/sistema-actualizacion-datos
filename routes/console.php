<?php

use App\Models\Bitacora;
use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\Usuario;
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
    Bitacora::registrar(null, $this->option('quitar') ? 'quitar_admin' : 'nombrar_admin', $usuario, 'Desde la consola (php artisan sacpa:admin)');

    $this->info("{$usuario->usua_nombre} {$usuario->usua_apellido_paterno} ({$numero_trabajador}) ahora tiene el rol {$rol}.");

    return 0;
})->purpose('Nombra (o quita con --quitar) a un administrador de SACPA por su número de trabajador');

/*
| Consultar la bitácora (no hay pantalla para esto en el Figma).
|
|   php artisan sacpa:bitacora                         → últimos 50 registros
|   php artisan sacpa:bitacora --profesor=2903872      → los de un profesor (por número de trabajador)
|   php artisan sacpa:bitacora --accion=login_fallido  → solo una acción
|   php artisan sacpa:bitacora --limite=200
*/
Artisan::command('sacpa:bitacora {--profesor= : Número de trabajador del profesor afectado} {--accion= : Código de la acción, ej. login_fallido} {--limite=50 : Cuántos registros mostrar}', function () {
    $consulta = Bitacora::with(['usuario', 'profesor'])->latest('bita_fecha_hora')->latest('bita_id_bitacora');

    $numero = $this->option('profesor');

    if (is_string($numero) && trim($numero) !== '') {
        $numero = trim($numero);
        $idUsuario = PersonaContacto::titular()->delProfesor($numero)->value('peco_id_usuario');

        if ($idUsuario === null) {
            $this->error("No hay ningún usuario con el número de trabajador {$numero}.");

            return 1;
        }

        $consulta->where('bita_id_profesor', $idUsuario);
    }

    $accion = $this->option('accion');

    if (is_string($accion) && $accion !== '') {
        $consulta->where('bita_accion', $accion);
    }

    $nombre = fn (?Usuario $usuario): string => $usuario === null
        ? '—'
        : trim("{$usuario->usua_nombre} {$usuario->usua_apellido_paterno}");

    $this->table(
        ['Fecha', 'Acción', 'Quién', 'Profesor afectado', 'Detalle'],
        $consulta->limit(max(1, (int) $this->option('limite')))->get()->map(fn (Bitacora $registro) => [
            $registro->bita_fecha_hora->format('d/m/Y H:i:s'),
            $registro->bita_accion,
            $nombre($registro->usuario),
            $nombre($registro->profesor),
            $registro->bita_detalle ?? '',
        ]),
    );

    return 0;
})->purpose('Muestra los registros de la bitácora de SACPA');
