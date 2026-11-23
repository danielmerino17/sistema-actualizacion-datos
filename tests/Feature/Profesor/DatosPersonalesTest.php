<?php

use App\Models\Bitacora;
use App\Models\Contacto;
use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\TipoContacto;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CatalogosSeeder::class, RolesSeeder::class]);
});

/**
 * Crea un usuario con su persona titular (sus "datos personales").
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

function tipo(string $nombre): int
{
    return TipoContacto::where('tico_tipo_contacto', $nombre)->value('tico_id_tipo_contacto');
}

function agregarContacto(Usuario $usuario, string $tipo, string $descripcion, bool $principal = false): Contacto
{
    return $usuario->datosPersonales->contactos()->create([
        'cont_id_tipo_contacto' => tipo($tipo),
        'cont_descripcion' => $descripcion,
        'cont_es_principal' => $principal,
    ]);
}

// ---------------------------------------------------------------- Acceso

test('un invitado es enviado al login', function () {
    $this->get('/datos-personales')->assertRedirect(route('login'));
});

test('un administrador no puede entrar a la pantalla del profesor', function () {
    $this->actingAs(crearProfesor(rol: Rol::ADMINISTRADOR))
        ->get('/datos-personales')
        ->assertForbidden();
});

test('un usuario inactivo no puede entrar', function () {
    $this->actingAs(crearProfesor(activo: false))
        ->get('/datos-personales')
        ->assertForbidden();
});

// ---------------------------------------------------------------- Consulta

test('el profesor ve solo sus propios datos', function () {
    $profesor = crearProfesor('2903872');
    $otro = crearProfesor('1481193');
    agregarContacto($profesor, 'Correo', 'mio@prueba.test', true);
    agregarContacto($otro, 'Correo', 'ajeno@prueba.test', true);

    $this->actingAs($profesor)
        ->get('/datos-personales')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profesor/DatosPersonales')
            ->where('profesor.numeroTrabajador', '2903872')
            ->has('contactos', 1)
            ->where('contactos.0.descripcion', 'mio@prueba.test')
            ->where('contactos.0.esPrincipal', true)
            ->has('tiposContacto', 5)
        );
});

test('pide actualizar si pasaron más de 6 meses', function () {
    $profesor = crearProfesor();
    $profesor->datosPersonales->forceFill([
        'peco_fecha_ultima_actualizacion' => Carbon::today()->subMonths(7),
    ])->saveQuietly();

    $this->actingAs($profesor)
        ->get('/datos-personales')
        ->assertInertia(fn (Assert $page) => $page->where('requiereActualizacion', true));
});

test('no pide actualizar si los datos son recientes', function () {
    $profesor = crearProfesor(); // al crearse queda con la fecha de hoy

    $this->actingAs($profesor)
        ->get('/datos-personales')
        ->assertInertia(fn (Assert $page) => $page->where('requiereActualizacion', false));
});

// ---------------------------------------------------------------- Alta

test('el profesor agrega un correo', function () {
    $profesor = crearProfesor();
    $profesor->datosPersonales->forceFill([
        'peco_fecha_ultima_actualizacion' => Carbon::today()->subYear(),
    ])->saveQuietly();

    $this->actingAs($profesor)
        ->from('/datos-personales')
        ->post('/datos-personales', [
            'tipo_contacto' => tipo('Correo'),
            'descripcion' => '  nuevo@prueba.test ',
            'es_principal' => 1,
        ])
        ->assertRedirect('/datos-personales')
        ->assertSessionHasNoErrors();

    expect(Contacto::where('cont_descripcion', 'nuevo@prueba.test')->exists())->toBeTrue()
        ->and($profesor->datosPersonales->fresh()->peco_fecha_ultima_actualizacion->isToday())->toBeTrue()
        ->and(Bitacora::where('bita_accion', 'crear_dato_personal')->count())->toBe(1);
});

test('valida el formato según el tipo de medio', function (string $tipo, string $descripcion) {
    $this->actingAs(crearProfesor())
        ->post('/datos-personales', [
            'tipo_contacto' => tipo($tipo),
            'descripcion' => $descripcion,
            'es_principal' => 0,
        ])
        ->assertSessionHasErrors('descripcion');
})->with([
    'correo sin arroba' => ['Correo', 'correo-invalido'],
    'celular corto' => ['Celular', '55 1234'],
    'teléfono con letras' => ['Casa', '55ABCD1234'],
]);

test('acepta teléfonos con espacios', function () {
    $this->actingAs(crearProfesor())
        ->post('/datos-personales', [
            'tipo_contacto' => tipo('Celular'),
            'descripcion' => '55 1928 0404',
            'es_principal' => 1,
        ])
        ->assertSessionHasNoErrors();
});

test('pide todos los campos', function () {
    $this->actingAs(crearProfesor())
        ->post('/datos-personales', [])
        ->assertSessionHasErrors(['tipo_contacto', 'descripcion', 'es_principal']);
});

test('solo hay un dato principal por tipo de medio', function () {
    $profesor = crearProfesor();
    $anterior = agregarContacto($profesor, 'Celular', '5511111111', true);
    $correo = agregarContacto($profesor, 'Correo', 'mio@prueba.test', true);

    $this->actingAs($profesor)->post('/datos-personales', [
        'tipo_contacto' => tipo('Celular'),
        'descripcion' => '5522222222',
        'es_principal' => 1,
    ]);

    expect($anterior->fresh()->cont_es_principal)->toBeFalse()
        ->and($correo->fresh()->cont_es_principal)->toBeTrue();
});

// ---------------------------------------------------------------- Edición y baja

test('el profesor edita su contacto', function () {
    $profesor = crearProfesor();
    $contacto = agregarContacto($profesor, 'Correo', 'viejo@prueba.test');

    $this->actingAs($profesor)
        ->put("/datos-personales/{$contacto->cont_id_contacto}", [
            'tipo_contacto' => tipo('Correo'),
            'descripcion' => 'nuevo@prueba.test',
            'es_principal' => 1,
        ])
        ->assertSessionHasNoErrors();

    expect($contacto->fresh())
        ->cont_descripcion->toBe('nuevo@prueba.test')
        ->cont_es_principal->toBeTrue();
});

test('el profesor elimina su contacto', function () {
    $profesor = crearProfesor();
    $contacto = agregarContacto($profesor, 'Correo', 'mio@prueba.test');

    $this->actingAs($profesor)
        ->delete("/datos-personales/{$contacto->cont_id_contacto}")
        ->assertRedirect();

    expect(Contacto::find($contacto->cont_id_contacto))->toBeNull()
        ->and(Bitacora::where('bita_accion', 'borrar_dato_personal')->count())->toBe(1);
});

test('no puede editar ni eliminar contactos de otro profesor', function () {
    $profesor = crearProfesor('2903872');
    $ajeno = agregarContacto(crearProfesor('1481193'), 'Correo', 'ajeno@prueba.test');

    $this->actingAs($profesor)
        ->put("/datos-personales/{$ajeno->cont_id_contacto}", [
            'tipo_contacto' => tipo('Correo'),
            'descripcion' => 'hackeado@prueba.test',
            'es_principal' => 1,
        ])
        ->assertNotFound();

    $this->actingAs($profesor)
        ->delete("/datos-personales/{$ajeno->cont_id_contacto}")
        ->assertNotFound();

    expect($ajeno->fresh()->cont_descripcion)->toBe('ajeno@prueba.test');
});

test('no puede tocar los contactos de sus contactos de emergencia desde esta pantalla', function () {
    $profesor = crearProfesor();
    $emergencia = PersonaContacto::create([
        'peco_id_usuario' => $profesor->usua_id_usuario,
        'peco_id_parentesco' => 1,
        'peco_numero_trabajador' => '2903872',
        'peco_nombre' => 'Rosa',
        'peco_apellido_paterno' => 'López',
    ]);
    $telefonoMadre = $emergencia->contactos()->create([
        'cont_id_tipo_contacto' => tipo('Celular'),
        'cont_descripcion' => '5520000000',
    ]);

    $this->actingAs($profesor)
        ->delete("/datos-personales/{$telefonoMadre->cont_id_contacto}")
        ->assertNotFound();
});
