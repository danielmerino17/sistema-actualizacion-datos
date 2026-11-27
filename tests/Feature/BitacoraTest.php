<?php

/*
| Bitácora (punto 6), búsqueda con % y _ (punto 9) y bd_fca sin respuesta (punto 10).
*/

use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([CatalogosSeeder::class, RolesSeeder::class]);
});

// crearProfesor(), tipo(), parentesco(), agregarContacto(), agregarEmergencia() y bdFcaDePrueba() están en tests/Pest.php

function ultimoRegistro(string $accion): Bitacora
{
    return Bitacora::where('bita_accion', $accion)->latest('bita_id_bitacora')->firstOrFail();
}

// ------------------------------------------------- 6. Quién, a quién y qué

test('cuando el profesor cambia sus datos queda quién, a quién y qué registro', function () {
    $profesor = crearProfesor('2903872');

    $this->actingAs($profesor)->post('/datos-personales', [
        'tipo_contacto' => tipo('Correo'),
        'descripcion' => 'mio@prueba.test',
        'es_principal' => 1,
    ]);

    $registro = ultimoRegistro('crear_dato_personal');
    $contacto = $profesor->datosPersonales->contactos()->firstOrFail();

    expect($registro->bita_id_usuario)->toBe($profesor->usua_id_usuario)
        ->and($registro->bita_id_profesor)->toBe($profesor->usua_id_usuario)
        ->and($registro->bita_detalle)->toBe("contacto #{$contacto->cont_id_contacto}");
});

test('cuando el administrador cambia datos de un profesor queda el administrador y el profesor', function () {
    $profesor = crearProfesor('2903872');
    $admin = crearProfesor('9000001', rol: Rol::ADMINISTRADOR);
    $contacto = agregarEmergencia($profesor);

    $this->actingAs($admin)->delete(
        "/admin/profesores/{$profesor->usua_id_usuario}/datos-de-emergencia/{$contacto->peco_id_persona_contacto}",
    );

    $registro = ultimoRegistro('borrar_emergencia');

    expect($registro->bita_id_usuario)->toBe($admin->usua_id_usuario)
        ->and($registro->bita_id_profesor)->toBe($profesor->usua_id_usuario)
        ->and($registro->bita_detalle)->toBe("contacto de emergencia #{$contacto->peco_id_persona_contacto}");
});

test('queda registrado cuando un administrador consulta o descarga los datos de un profesor', function () {
    $profesor = crearProfesor('2903872');
    $admin = crearProfesor('9000001', rol: Rol::ADMINISTRADOR);
    $base = "/admin/profesores/{$profesor->usua_id_usuario}";

    $this->actingAs($admin)->get("{$base}/datos-personales")->assertOk();
    $this->actingAs($admin)->get("{$base}/datos-de-emergencia")->assertOk();
    $this->actingAs($admin)->get("{$base}/datos-personales/excel")->assertOk();

    foreach (['consulta_personales', 'consulta_emergencia', 'excel_datos_person'] as $accion) {
        $registro = ultimoRegistro($accion);

        expect($registro->bita_id_usuario)->toBe($admin->usua_id_usuario)
            ->and($registro->bita_id_profesor)->toBe($profesor->usua_id_usuario);
    }
});

test('el profesor que consulta sus propios datos no llena la bitácora', function () {
    $this->actingAs(crearProfesor())->get('/datos-personales')->assertOk();

    expect(Bitacora::where('bita_accion', 'consulta_personales')->count())->toBe(0);
});

// ------------------------------------------------- 6. Login y logout

test('un intento de login fallido queda con el número y la IP, pero nunca el RFC', function () {
    bdFcaDePrueba(['2903872' => 'JIHJ800101AB1']);

    $this->post('/login', ['numero_trabajador' => '2903872', 'rfc' => 'XXXX000000XX0']);

    $registro = ultimoRegistro('login_fallido');

    expect($registro->bita_id_usuario)->toBeNull()
        ->and($registro->bita_detalle)->toContain('2903872')
        ->and($registro->bita_detalle)->toContain('127.0.0.1')
        ->and($registro->bita_detalle)->not->toContain('XXXX000000XX0');
});

test('el inicio de sesión queda registrado con la IP', function () {
    $profesor = crearProfesor('2903872');
    bdFcaDePrueba(['2903872' => 'JIHJ800101AB1']);

    $this->post('/login', ['numero_trabajador' => '2903872', 'rfc' => 'JIHJ800101AB1']);

    $registro = ultimoRegistro('inicio_sesion');

    expect($registro->bita_id_usuario)->toBe($profesor->usua_id_usuario)
        ->and($registro->bita_id_profesor)->toBe($profesor->usua_id_usuario)
        ->and($registro->bita_detalle)->toContain('127.0.0.1');
});

test('el intento de un usuario desactivado queda registrado', function () {
    $profesor = crearProfesor('2903872', activo: false);
    bdFcaDePrueba(['2903872' => 'JIHJ800101AB1']);

    $this->post('/login', ['numero_trabajador' => '2903872', 'rfc' => 'JIHJ800101AB1'])
        ->assertSessionHasErrors('numero_trabajador');

    expect(ultimoRegistro('login_desactivado')->bita_id_profesor)->toBe($profesor->usua_id_usuario);
});

test('el cierre de sesión queda registrado', function () {
    $profesor = crearProfesor();

    $this->actingAs($profesor)->post('/logout');

    expect(ultimoRegistro('cierre_sesion')->bita_id_usuario)->toBe($profesor->usua_id_usuario);
});

// ------------------------------------------------- 6. Comandos

test('nombrar administrador desde la consola queda registrado sin usuario', function () {
    $profesor = crearProfesor('2903872');

    $this->artisan('sacpa:admin', ['numero_trabajador' => '2903872'])->assertSuccessful();

    $registro = ultimoRegistro('nombrar_admin');

    expect($registro->bita_id_usuario)->toBeNull()
        ->and($registro->bita_id_profesor)->toBe($profesor->usua_id_usuario);
});

test('el comando sacpa:bitacora muestra los registros de un profesor', function () {
    $profesor = crearProfesor('2903872');
    $otro = crearProfesor('1481193');
    Bitacora::registrar($profesor, 'inicio_sesion', $profesor, 'del profesor');
    Bitacora::registrar($otro, 'inicio_sesion', $otro, 'de otro');

    $this->artisan('sacpa:bitacora', ['--profesor' => '2903872'])
        ->expectsOutputToContain('del profesor')
        ->doesntExpectOutputToContain('de otro')
        ->assertSuccessful();

    $this->artisan('sacpa:bitacora', ['--profesor' => '0000000'])->assertFailed();
});

// ------------------------------------------------- 9. Búsqueda con % y _

test('en la búsqueda % y _ se buscan como texto, no como comodines', function (string $texto) {
    crearProfesor('2903872');
    crearProfesor('1481193');

    $this->actingAs(crearProfesor('9000001', rol: Rol::ADMINISTRADOR))
        ->from('/admin')
        ->get('/admin?nombre='.urlencode($texto))
        ->assertSessionHasErrors(['busqueda' => 'No se encontró ningún profesor con esos datos.']);
})->with(['%', '_', '%%', 'Prueba_']);

// ------------------------------------------------- 10. bd_fca sin respuesta

test('si bd_fca no responde, el login muestra un mensaje claro y no crea nada', function () {
    config(['database.connections.pgsql_fca' => ['driver' => 'sqlite', 'database' => base_path('no-existe/bd.sqlite'), 'prefix' => '']]);
    DB::purge('pgsql_fca');

    $this->from('/')
        ->post('/login', ['numero_trabajador' => '2903872', 'rfc' => 'JIHJ800101AB1'])
        ->assertRedirect('/')
        ->assertSessionHasErrors(['numero_trabajador' => 'No fue posible validar tus datos en este momento. Intenta de nuevo en unos minutos.']);

    $this->assertGuest();
    expect(Usuario::count())->toBe(0);
});
