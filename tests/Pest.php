<?php

use App\Models\Contacto;
use App\Models\Parentesco;
use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\TipoContacto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Crea un usuario con su persona titular (sus "datos personales").
 * Requiere haber sembrado CatalogosSeeder y RolesSeeder.
 */
function crearProfesor(string $numero = '2903872', string $rol = Rol::PROFESOR, bool $activo = true): Usuario
{
    $usuario = Usuario::create([
        'usua_id_rol' => Rol::where('rol_nombre_rol', $rol)->value('rol_id_rol'),
        'usua_nombre' => 'Profesor',
        'usua_apellido_paterno' => "Prueba {$numero}",
        'usua_correo' => "{$numero}@prueba.test",
        'usua_activo' => $activo,
    ]);

    $usuario->personas()->create([
        'peco_numero_trabajador' => $numero,
        'peco_nombre' => 'Profesor',
        'peco_apellido_paterno' => "Prueba {$numero}",
    ]);

    return $usuario;
}

/** Id del catálogo tipo_contacto (Correo, Celular, Casa...). */
function tipo(string $nombre): int
{
    return TipoContacto::where('tico_tipo_contacto', $nombre)->value('tico_id_tipo_contacto');
}

/** Id del catálogo parentesco (Madre, Padre, Hermano(a)...). */
function parentesco(string $nombre): int
{
    return Parentesco::where('pare_parentesco', $nombre)->value('pare_id_parentesco');
}

/** Agrega un correo o teléfono a los datos personales del profesor. */
function agregarContacto(Usuario $usuario, string $tipo, string $descripcion, bool $principal = false): Contacto
{
    return $usuario->datosPersonales->contactos()->create([
        'cont_id_tipo_contacto' => tipo($tipo),
        'cont_descripcion' => $descripcion,
        'cont_es_principal' => $principal,
    ]);
}

/** Agrega un contacto de emergencia (con su celular) al profesor. */
function agregarEmergencia(Usuario $usuario, string $nombre = 'Rosa', string $parentesco = 'Madre', string $celular = '5520000000'): PersonaContacto
{
    $persona = $usuario->personas()->create([
        'peco_id_parentesco' => parentesco($parentesco),
        'peco_numero_trabajador' => $usuario->datosPersonales->peco_numero_trabajador,
        'peco_nombre' => $nombre,
        'peco_apellido_paterno' => 'López',
    ]);

    $persona->contactos()->create([
        'cont_id_tipo_contacto' => tipo('Celular'),
        'cont_descripcion' => $celular,
        'cont_es_principal' => true,
    ]);

    return $persona;
}
