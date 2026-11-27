<?php

use App\Models\Bitacora;
use App\Models\Contacto;
use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    $this->seed([CatalogosSeeder::class, RolesSeeder::class]);
});

// crearProfesor(), tipo(), parentesco(), agregarContacto() y agregarEmergencia() están en tests/Pest.php

function crearAdmin(): Usuario
{
    return crearProfesor('9000001', rol: Rol::ADMINISTRADOR);
}

/**
 * Profesor con nombre real para probar la búsqueda.
 */
function profesorConNombre(string $numero, string $nombre, string $paterno, ?string $materno = null): Usuario
{
    $usuario = crearProfesor($numero);
    $usuario->datosPersonales->update([
        'peco_nombre' => $nombre,
        'peco_apellido_paterno' => $paterno,
        'peco_apellido_materno' => $materno,
    ]);

    return $usuario->fresh();
}

// bdFcaDePrueba() está en tests/Pest.php

// ---------------------------------------------------------------- Acceso

test('un invitado no puede entrar a la búsqueda', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('un profesor no puede entrar a las pantallas de administrador', function () {
    $profesor = crearProfesor('2903872');
    $otro = crearProfesor('1481193');

    $this->actingAs($profesor)->get('/admin')->assertForbidden();
    $this->actingAs($profesor)->get("/admin/profesores/{$otro->usua_id_usuario}/datos-personales")->assertForbidden();
    $this->actingAs($profesor)->get("/admin/profesores/{$otro->usua_id_usuario}/datos-personales/excel")->assertForbidden();
});

test('el profesor no tiene descarga de Excel en sus pantallas', function () {
    $this->actingAs(crearProfesor())->get('/datos-personales/excel')->assertNotFound();
});

test('un administrador con sesión que abre el login va a la búsqueda', function () {
    $this->actingAs(crearAdmin())->get('/')->assertRedirect(route('admin.buscar'));
});

// ---------------------------------------------------------------- Búsqueda

test('la búsqueda muestra el formulario vacío', function () {
    $this->actingAs(crearAdmin())
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Buscar')
            ->where('resultados', null)
        );
});

test('pide al menos un dato para buscar', function () {
    $this->actingAs(crearAdmin())
        ->from('/admin')
        ->get('/admin?nombre=&numero_trabajador=&rfc=')
        ->assertSessionHasErrors('busqueda');
});

test('por número de trabajador entra directo a los datos del profesor', function () {
    $profesor = crearProfesor('2903872');

    $this->actingAs(crearAdmin())
        ->get('/admin?numero_trabajador=2903872')
        ->assertRedirect(route('admin.profesores.datos-personales.index', ['profesor' => $profesor->usua_id_usuario]));
});

test('por nombre busca sin importar mayúsculas y con nombre parcial', function () {
    $luis = profesorConNombre('1481193', 'Luis Miguel', 'Torres', 'Arroyo');
    profesorConNombre('2903872', 'José Martín', 'Jiménez', 'Hernández');

    $this->actingAs(crearAdmin())
        ->get('/admin?nombre='.urlencode('torres arroyo'))
        ->assertRedirect(route('admin.profesores.datos-personales.index', ['profesor' => $luis->usua_id_usuario]));
});

test('si hay varios profesores con ese nombre muestra la lista', function () {
    profesorConNombre('1481193', 'Luis Miguel', 'Torres', 'Arroyo');
    profesorConNombre('3000001', 'Luis', 'Pérez', 'Torres');

    $this->actingAs(crearAdmin())
        ->get('/admin?nombre=Luis')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Buscar')
            ->has('resultados', 2)
            ->where('resultados.0.nombre', 'Luis Pérez Torres')
        );
});

test('la búsqueda no encuentra administradores', function () {
    $admin = crearAdmin(); // número 9000001

    $this->actingAs($admin)
        ->from('/admin')
        ->get('/admin?numero_trabajador=9000001')
        ->assertSessionHasErrors('busqueda');
});

test('si no encuentra a nadie avisa', function () {
    crearProfesor('2903872');

    $this->actingAs(crearAdmin())
        ->from('/admin')
        ->get('/admin?numero_trabajador=1111111')
        ->assertSessionHasErrors(['busqueda' => 'No se encontró ningún profesor con esos datos.']);
});

test('por RFC consulta bd_fca para obtener el número de trabajador', function () {
    $profesor = crearProfesor('2903872');
    bdFcaDePrueba(['2903872' => 'JIHJ800101AB1']);

    $this->actingAs(crearAdmin())
        ->get('/admin?rfc=jihj800101ab1')
        ->assertRedirect(route('admin.profesores.datos-personales.index', ['profesor' => $profesor->usua_id_usuario]));
});

test('si bd_fca no responde, la búsqueda por RFC muestra un mensaje', function () {
    crearProfesor('2903872');
    config(['database.connections.pgsql_fca' => ['driver' => 'sqlite', 'database' => base_path('no-existe/bd.sqlite'), 'prefix' => '']]);
    DB::purge('pgsql_fca');

    $this->actingAs(crearAdmin())
        ->from('/admin')
        ->get('/admin?rfc=JIHJ800101AB1')
        ->assertSessionHasErrors('rfc');
});

// ---------------------------------------------------------------- Consulta y modificación

test('el administrador ve los datos personales del profesor', function () {
    $profesor = crearProfesor('2903872');
    agregarContacto($profesor, 'Correo', 'profe@prueba.test', true);

    $this->actingAs(crearAdmin())
        ->get("/admin/profesores/{$profesor->usua_id_usuario}/datos-personales")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profesor/DatosPersonales')
            ->where('esAdministrador', true)
            ->where('rutaProfesor', "/admin/profesores/{$profesor->usua_id_usuario}")
            ->where('profesor.numeroTrabajador', '2903872')
            ->has('contactos', 1)
        );
});

test('el profesor ve su pantalla sin opciones de administrador', function () {
    $this->actingAs(crearProfesor())
        ->get('/datos-personales')
        ->assertInertia(fn (Assert $page) => $page
            ->where('esAdministrador', false)
            ->where('rutaProfesor', '')
        );
});

test('el administrador agrega un dato al profesor y la bitácora registra al administrador', function () {
    $profesor = crearProfesor('2903872');
    $admin = crearAdmin();

    $this->actingAs($admin)
        ->post("/admin/profesores/{$profesor->usua_id_usuario}/datos-personales", [
            'tipo_contacto' => tipo('Correo'),
            'descripcion' => 'nuevo@prueba.test',
            'es_principal' => 1,
        ])
        ->assertSessionHasNoErrors();

    expect($profesor->datosPersonales->contactos()->where('cont_descripcion', 'nuevo@prueba.test')->exists())->toBeTrue()
        ->and($admin->datosPersonales->contactos()->count())->toBe(0)
        ->and(Bitacora::where('bita_accion', 'crear_dato_personal')->value('bita_id_usuario'))->toBe($admin->usua_id_usuario);
});

test('el administrador edita y elimina contactos de emergencia del profesor', function () {
    $profesor = crearProfesor('2903872');
    $contacto = agregarEmergencia($profesor, 'Rosa');
    $base = "/admin/profesores/{$profesor->usua_id_usuario}/datos-de-emergencia";
    $admin = crearAdmin();

    $this->actingAs($admin)
        ->put("{$base}/{$contacto->peco_id_persona_contacto}", [
            'nombre' => 'Rosa María',
            'apellido_paterno' => 'López',
            'parentesco' => parentesco('Madre'),
            'telefono_celular' => '5520000000',
        ])
        ->assertSessionHasNoErrors();

    expect($contacto->fresh()->peco_nombre)->toBe('Rosa María');

    $this->actingAs($admin)
        ->delete("{$base}/{$contacto->peco_id_persona_contacto}")
        ->assertRedirect();

    expect(PersonaContacto::find($contacto->peco_id_persona_contacto))->toBeNull();
});

test('no se puede usar la ruta de un profesor para tocar datos de otro', function () {
    $profesorA = crearProfesor('2903872');
    $profesorB = crearProfesor('1481193');
    $contactoDeB = agregarContacto($profesorB, 'Correo', 'b@prueba.test');

    $this->actingAs(crearAdmin())
        ->delete("/admin/profesores/{$profesorA->usua_id_usuario}/datos-personales/{$contactoDeB->cont_id_contacto}")
        ->assertNotFound();

    expect(Contacto::find($contactoDeB->cont_id_contacto))->not->toBeNull();
});

test('un administrador no aparece como profesor en las rutas de administrador', function () {
    $otroAdmin = crearProfesor('9000002', rol: Rol::ADMINISTRADOR);

    $this->actingAs(crearAdmin())
        ->get("/admin/profesores/{$otroAdmin->usua_id_usuario}/datos-personales")
        ->assertNotFound();
});

// ---------------------------------------------------------------- Excel

test('descarga los datos personales en Excel con el apellido en el nombre', function () {
    $profesor = profesorConNombre('1481193', 'Luis Miguel', 'Torres', 'Arroyo');
    agregarContacto($profesor, 'Correo', 'luis@prueba.test', true);

    $respuesta = $this->actingAs(crearAdmin())
        ->get("/admin/profesores/{$profesor->usua_id_usuario}/datos-personales/excel")
        ->assertOk()
        ->assertDownload('DatosPersonales_Torres.xlsx');

    $filas = leerExcel($respuesta->baseResponse->getFile()->getPathname());
    $titulos = filaQueEmpiezaCon($filas, 'Medio');

    expect($filas[0][0])->toBe('Datos personales')
        ->and($filas[1][1])->toBe('Luis Miguel Torres Arroyo')
        ->and($filas[$titulos])->toBe(['Medio', 'Datos de contacto', 'Tipo de uso', 'Última fecha de actualización'])
        ->and($filas[$titulos + 1][0])->toBe('Correo')
        ->and($filas[$titulos + 1][1])->toBe('luis@prueba.test')
        ->and($filas[$titulos + 1][2])->toBe('Principal')
        ->and(Bitacora::where('bita_accion', 'excel_datos_person')->count())->toBe(1);
});

test('descarga los datos de emergencia en Excel', function () {
    $profesor = profesorConNombre('1481193', 'Luis Miguel', 'Peña', 'Arroyo');
    agregarEmergencia($profesor, 'Rosa', 'Madre', '5520000000');

    $respuesta = $this->actingAs(crearAdmin())
        ->get("/admin/profesores/{$profesor->usua_id_usuario}/datos-de-emergencia/excel")
        ->assertOk()
        ->assertDownload('DatosEmergencia_Pena.xlsx');

    $filas = leerExcel($respuesta->baseResponse->getFile()->getPathname());
    $primera = filaQueEmpiezaCon($filas, 'No. Contacto') + 1;

    expect($filas[$primera][1])->toBe('Rosa')
        ->and($filas[$primera][4])->toBe('Madre')
        ->and($filas[$primera][5])->toBe('5520000000');
});

// ---------------------------------------------------------------- Comando sacpa:admin

test('el comando sacpa:admin nombra y quita administradores', function () {
    $profesor = crearProfesor('2903872');

    $this->artisan('sacpa:admin', ['numero_trabajador' => '2903872'])->assertSuccessful();
    expect($profesor->fresh()->tieneRol(Rol::ADMINISTRADOR))->toBeTrue();

    $this->artisan('sacpa:admin', ['numero_trabajador' => '2903872', '--quitar' => true])->assertSuccessful();
    expect($profesor->fresh()->tieneRol(Rol::PROFESOR))->toBeTrue();
});

test('el comando sacpa:admin avisa si el número no existe', function () {
    $this->artisan('sacpa:admin', ['numero_trabajador' => '1111111'])->assertFailed();
});

/**
 * Índice de la primera fila cuya primera celda es $texto.
 *
 * @param  array<int, array<int, mixed>>  $filas
 */
function filaQueEmpiezaCon(array $filas, string $texto): int
{
    foreach ($filas as $indice => $fila) {
        if (($fila[0] ?? null) === $texto) {
            return $indice;
        }
    }

    throw new RuntimeException("No hay ninguna fila que empiece con '{$texto}'.");
}

/**
 * @return array<int, array<int, mixed>>
 */
function leerExcel(string $ruta): array
{
    $reader = new Reader;
    $reader->open($ruta);
    $filas = [];

    foreach ($reader->getSheetIterator() as $hoja) {
        foreach ($hoja->getRowIterator() as $fila) {
            $filas[] = $fila->toArray();
        }
        break;
    }

    $reader->close();

    return $filas;
}
