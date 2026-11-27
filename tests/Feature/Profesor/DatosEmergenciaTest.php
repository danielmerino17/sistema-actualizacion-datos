<?php

use App\Models\Bitacora;
use App\Models\Contacto;
use App\Models\PersonaContacto;
use App\Models\Rol;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CatalogosSeeder::class, RolesSeeder::class]);
});

// crearProfesor(), parentesco() y agregarEmergencia() están en tests/Pest.php

/**
 * @return array<string, mixed>
 */
function datosEmergencia(array $cambios = []): array
{
    return [
        'nombre' => 'Ernesto José',
        'apellido_paterno' => 'López',
        'apellido_materno' => 'Mejía',
        'parentesco' => parentesco('Hermano(a)'),
        'telefono_celular' => '55 5299 3963',
        'telefono_alterno' => '55 5019 3773',
        ...$cambios,
    ];
}

// ---------------------------------------------------------------- Acceso

test('un invitado es enviado al login', function () {
    $this->get('/datos-de-emergencia')->assertRedirect(route('login'));
});

test('un administrador no puede entrar a la pantalla del profesor', function () {
    $this->actingAs(crearProfesor(rol: Rol::ADMINISTRADOR))
        ->get('/datos-de-emergencia')
        ->assertForbidden();
});

// ---------------------------------------------------------------- Consulta

test('el profesor ve solo sus contactos de emergencia, numerados', function () {
    $profesor = crearProfesor('2903872');
    agregarEmergencia($profesor, 'Rosa', 'Madre', '5520000000');
    agregarEmergencia($profesor, 'Pedro', 'Padre', '5530000000');
    agregarEmergencia(crearProfesor('1481193'), 'Ajena', 'Madre');

    $this->actingAs($profesor)
        ->get('/datos-de-emergencia')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profesor/DatosEmergencia')
            ->where('profesor.numeroTrabajador', '2903872')
            ->has('contactos', 2)
            ->where('contactos.0.numero', 1)
            ->where('contactos.0.nombre', 'Rosa')
            ->where('contactos.0.parentesco', 'Madre')
            ->where('contactos.0.celular', '5520000000')
            ->where('contactos.0.alterno', null)
            ->where('contactos.1.numero', 2)
            ->has('parentescos', 6)
        );
});

test('sus propios datos personales no aparecen como contacto de emergencia', function () {
    $profesor = crearProfesor();

    $this->actingAs($profesor)
        ->get('/datos-de-emergencia')
        ->assertInertia(fn (Assert $page) => $page->has('contactos', 0));
});

// ---------------------------------------------------------------- Alta

test('el profesor agrega un contacto de emergencia con dos teléfonos', function () {
    $profesor = crearProfesor();
    $profesor->datosPersonales->forceFill([
        'peco_fecha_ultima_actualizacion' => Carbon::today()->subYear(),
    ])->saveQuietly();

    $this->actingAs($profesor)
        ->from('/datos-de-emergencia')
        ->post('/datos-de-emergencia', datosEmergencia())
        ->assertRedirect('/datos-de-emergencia')
        ->assertSessionHasNoErrors();

    $contacto = $profesor->contactosEmergencia()->with('contactos')->sole();

    expect($contacto->peco_nombre)->toBe('Ernesto José')
        ->and($contacto->peco_numero_trabajador)->toBe('2903872')
        ->and($contacto->peco_id_parentesco)->toBe(parentesco('Hermano(a)'))
        ->and($contacto->contactos->firstWhere('cont_es_principal', true)->cont_descripcion)->toBe('55 5299 3963')
        ->and($contacto->contactos->firstWhere('cont_es_principal', false)->cont_descripcion)->toBe('55 5019 3773')
        ->and($profesor->datosPersonales->fresh()->peco_fecha_ultima_actualizacion->isToday())->toBeTrue()
        ->and(Bitacora::where('bita_accion', 'crear_emergencia')->count())->toBe(1);
});

test('el teléfono alterno y el apellido materno son opcionales', function () {
    $profesor = crearProfesor();

    $this->actingAs($profesor)
        ->post('/datos-de-emergencia', datosEmergencia(['telefono_alterno' => '', 'apellido_materno' => '']))
        ->assertSessionHasNoErrors();

    $contacto = $profesor->contactosEmergencia()->sole();

    expect($contacto->peco_apellido_materno)->toBeNull()
        ->and($contacto->contactos()->count())->toBe(1);
});

test('pide los campos obligatorios', function () {
    $this->actingAs(crearProfesor())
        ->post('/datos-de-emergencia', [])
        ->assertSessionHasErrors(['nombre', 'apellido_paterno', 'parentesco', 'telefono_celular'])
        ->assertSessionDoesntHaveErrors(['apellido_materno', 'telefono_alterno']);
});

test('valida los teléfonos y el parentesco', function (array $cambios, string $campo) {
    $this->actingAs(crearProfesor())
        ->post('/datos-de-emergencia', datosEmergencia($cambios))
        ->assertSessionHasErrors($campo);
})->with([
    'celular corto' => [['telefono_celular' => '55 1234'], 'telefono_celular'],
    'celular con letras' => [['telefono_celular' => '55ABCD1234'], 'telefono_celular'],
    'alterno con 11 dígitos' => [['telefono_alterno' => '55123456789'], 'telefono_alterno'],
    'parentesco inexistente' => [['parentesco' => 999], 'parentesco'],
]);

// ---------------------------------------------------------------- Edición y baja

test('el profesor edita un contacto de emergencia y puede quitar el alterno', function () {
    $profesor = crearProfesor();

    $this->actingAs($profesor)->post('/datos-de-emergencia', datosEmergencia());
    $contacto = $profesor->contactosEmergencia()->sole();

    $this->actingAs($profesor)
        ->put("/datos-de-emergencia/{$contacto->peco_id_persona_contacto}", datosEmergencia([
            'nombre' => 'Ernesto',
            'parentesco' => parentesco('Padre'),
            'telefono_celular' => '5511112222',
            'telefono_alterno' => '',
        ]))
        ->assertSessionHasNoErrors();

    $contacto->refresh()->load('contactos');

    expect($contacto->peco_nombre)->toBe('Ernesto')
        ->and($contacto->peco_id_parentesco)->toBe(parentesco('Padre'))
        ->and($contacto->contactos)->toHaveCount(1)
        ->and($contacto->contactos->first()->cont_descripcion)->toBe('5511112222')
        ->and(Bitacora::where('bita_accion', 'editar_emergencia')->count())->toBe(1);
});

test('el profesor elimina un contacto de emergencia junto con sus teléfonos', function () {
    $profesor = crearProfesor();
    $contacto = agregarEmergencia($profesor);

    $this->actingAs($profesor)
        ->delete("/datos-de-emergencia/{$contacto->peco_id_persona_contacto}")
        ->assertRedirect();

    expect(PersonaContacto::find($contacto->peco_id_persona_contacto))->toBeNull()
        ->and(Contacto::where('cont_id_persona_contacto', $contacto->peco_id_persona_contacto)->count())->toBe(0)
        ->and(Bitacora::where('bita_accion', 'borrar_emergencia')->count())->toBe(1);
});

test('no puede editar ni eliminar contactos de emergencia de otro profesor', function () {
    $profesor = crearProfesor('2903872');
    $ajeno = agregarEmergencia(crearProfesor('1481193'), 'Ajena');

    $this->actingAs($profesor)
        ->put("/datos-de-emergencia/{$ajeno->peco_id_persona_contacto}", datosEmergencia(['nombre' => 'Cambiado']))
        ->assertNotFound();

    $this->actingAs($profesor)
        ->delete("/datos-de-emergencia/{$ajeno->peco_id_persona_contacto}")
        ->assertNotFound();

    expect($ajeno->fresh()->peco_nombre)->toBe('Ajena');
});

test('no puede borrar sus propios datos personales desde esta pantalla', function () {
    $profesor = crearProfesor();
    $titular = $profesor->datosPersonales;

    $this->actingAs($profesor)
        ->delete("/datos-de-emergencia/{$titular->peco_id_persona_contacto}")
        ->assertNotFound();

    expect($titular->fresh())->not->toBeNull();
});
