<?php

/*
| Pruebas de las correcciones de la revisión de seguridad.
*/

use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed([CatalogosSeeder::class, RolesSeeder::class]);
});

// crearProfesor(), parentesco(), agregarEmergencia() y bdFcaDePrueba() están en tests/Pest.php

// ------------------------------------------------- 1. Login: nunca por correo

test('un profesor nuevo con un correo que ya usa otro usuario NO entra a esa cuenta', function () {
    $admin = crearProfesor('9000001', rol: Rol::ADMINISTRADOR);
    $admin->update(['usua_correo' => 'coordinacion@fca.test']);

    bdFcaDePrueba(['3000001' => [
        'rfc' => 'NUPR900101AA1', 'nombre' => 'Nuevo', 'paterno' => 'Profesor', 'correo' => 'coordinacion@fca.test',
    ]]);

    $this->post('/login', ['numero_trabajador' => '3000001', 'rfc' => 'NUPR900101AA1'])
        ->assertRedirect(route('datos-personales.index'));

    $nuevo = PersonaContacto::titular()->delProfesor('3000001')->firstOrFail()->usuario;

    $this->assertAuthenticatedAs($nuevo);
    expect($nuevo->usua_id_usuario)->not->toBe($admin->usua_id_usuario)
        ->and($nuevo->tieneRol(Rol::PROFESOR))->toBeTrue()
        ->and($nuevo->usua_correo)->toBe('3000001@fca.unam.mx') // correo de respaldo
        ->and($admin->fresh()->datosPersonales->peco_numero_trabajador)->toBe('9000001'); // el admin no cambió
});

test('un profesor nuevo con correo libre usa el correo de bd_fca', function () {
    bdFcaDePrueba(['3000001' => [
        'rfc' => 'NUPR900101AA1', 'nombre' => 'Ana', 'paterno' => 'López', 'correo' => 'Ana.Lopez@FCA.test',
    ]]);

    $this->post('/login', ['numero_trabajador' => '3000001', 'rfc' => 'NUPR900101AA1']);

    $usuario = Usuario::where('usua_correo', 'ana.lopez@fca.test')->firstOrFail();

    $this->assertAuthenticatedAs($usuario);
    expect($usuario->datosPersonales->peco_numero_trabajador)->toBe('3000001')
        ->and($usuario->datosPersonales->peco_nombre)->toBe('Ana');
});

test('un profesor que ya entró antes sigue entrando a su propia cuenta', function () {
    $profesor = crearProfesor('2903872');
    bdFcaDePrueba(['2903872' => ['rfc' => 'JIHJ800101AB1', 'correo' => 'otro.correo@fca.test']]);

    $this->post('/login', ['numero_trabajador' => '2903872', 'rfc' => 'JIHJ800101AB1']);

    $this->assertAuthenticatedAs($profesor);
    expect(Usuario::count())->toBe(1);
});

// ------------------------------------------------- 2. Accesos de prueba

test('los accesos de prueba no existen fuera de desarrollo', function (string $ruta) {
    $this->get($ruta)->assertNotFound();
    $this->assertGuest();
})->with(['/dev/entrar-como-admin', '/dev/entrar-como-profesor', '/demo/componentes']);

// ------------------------------------------------- 5. Excel sin fórmulas

test('un nombre que parece fórmula se rechaza al guardar el contacto de emergencia', function (string $nombre) {
    $this->actingAs(crearProfesor())
        ->post('/datos-de-emergencia', [
            'nombre' => $nombre,
            'apellido_paterno' => 'López',
            'parentesco' => parentesco('Madre'),
            'telefono_celular' => '5520000000',
        ])
        ->assertSessionHasErrors('nombre');
})->with([
    'fórmula' => ['=HYPERLINK("http://x","clic")'],
    'suma' => ['=1+1'],
    'con números' => ['Rosa123'],
    'con símbolos' => ['<script>'],
]);

test('acepta nombres con acentos, ñ, apóstrofo y guion', function () {
    $this->actingAs(crearProfesor())
        ->post('/datos-de-emergencia', [
            'nombre' => "María José O'Neil",
            'apellido_paterno' => 'Peña-Núñez',
            'parentesco' => parentesco('Madre'),
            'telefono_celular' => '5520000000',
        ])
        ->assertSessionHasNoErrors();
});

test('el Excel guarda los textos como texto aunque empiecen con "="', function () {
    $profesor = crearProfesor('2903872');
    // Se guarda directo en la base (como si viniera de antes de la validación).
    agregarEmergencia($profesor, '=1+1');

    $respuesta = $this->actingAs(crearProfesor('9000001', rol: Rol::ADMINISTRADOR))
        ->get("/admin/profesores/{$profesor->usua_id_usuario}/datos-de-emergencia/excel")
        ->assertOk();

    $zip = new ZipArchive;
    $zip->open($respuesta->baseResponse->getFile()->getPathname());
    $hoja = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    expect($hoja)->not->toContain('<f>')   // ninguna fórmula en el archivo
        ->and($hoja)->toContain('=1+1');   // el texto sí está, como texto
});
