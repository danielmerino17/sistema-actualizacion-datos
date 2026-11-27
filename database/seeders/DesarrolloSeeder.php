<?php

namespace Database\Seeders;

use App\Models\Parentesco;
use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\TipoContacto;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos INVENTADOS para trabajar en local sin bd_fca.
 * Solo se corre cuando APP_ENV=local (ver DatabaseSeeder).
 *
 * Crea dos profesores de prueba:
 *  - José Martín (2903872): el que usa el acceso de prueba /dev/entrar-como-profesor.
 *    Su última actualización es de hace 7 meses, para que salga el aviso amarillo.
 *  - Luis Miguel (1481193): otro profesor, para comprobar que nadie ve datos ajenos.
 */
class DesarrolloSeeder extends Seeder
{
    public const CORREO_PROFESOR_PRUEBA = 'profesor.prueba@sacpa.test';

    public function run(): void
    {
        $rolProfesor = Rol::where('rol_nombre_rol', Rol::PROFESOR)->firstOrFail();

        $jose = $this->crearProfesor(
            rolId: $rolProfesor->rol_id_rol,
            correo: self::CORREO_PROFESOR_PRUEBA,
            numeroTrabajador: '2903872',
            nombre: 'José Martín',
            apellidoPaterno: 'Jiménez',
            apellidoMaterno: 'Hernández',
            ultimaActualizacion: Carbon::today()->subMonths(7),
            contactos: [
                ['Correo', 'jmjh10@prueba.test', true],
                ['Celular', '5519280404', true],
                ['Casa', '5610102629', false],
            ],
        );

        $otro = $this->crearProfesor(
            rolId: $rolProfesor->rol_id_rol,
            correo: 'otro.profesor@sacpa.test',
            numeroTrabajador: '1481193',
            nombre: 'Luis Miguel',
            apellidoPaterno: 'Torres',
            apellidoMaterno: 'Arroyo',
            ultimaActualizacion: Carbon::today()->subMonth(),
            contactos: [
                ['Correo', 'luismito@prueba.test', true],
            ],
        );

        // Contactos de emergencia: [nombre, paterno, materno, parentesco, celular, alterno|null]
        $this->crearContactosEmergencia($jose, '2903872', [
            ['Ernesto José', 'López', 'Mejía', 'Otro', '5552993963', '5550193773'],
            ['Antonio', 'Jiménez', 'Rosas', 'Hermano(a)', '5519372037', null],
        ]);

        $this->crearContactosEmergencia($otro, '1481193', [
            ['Rosa', 'Arroyo', null, 'Madre', '5520000000', null],
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string|null, 3: string, 4: string, 5: string|null}>  $contactos
     */
    private function crearContactosEmergencia(Usuario $usuario, string $numeroTrabajador, array $contactos): void
    {
        // Si ya se había corrido el seeder, no se duplican.
        if ($usuario->contactosEmergencia()->exists()) {
            return;
        }

        $celular = TipoContacto::where('tico_tipo_contacto', 'Celular')->value('tico_id_tipo_contacto');
        $casa = TipoContacto::where('tico_tipo_contacto', 'Casa')->value('tico_id_tipo_contacto');

        foreach ($contactos as [$nombre, $paterno, $materno, $parentesco, $telefonoCelular, $telefonoAlterno]) {
            $persona = PersonaContacto::create([
                'peco_id_usuario' => $usuario->usua_id_usuario,
                'peco_id_parentesco' => Parentesco::where('pare_parentesco', $parentesco)->value('pare_id_parentesco'),
                'peco_numero_trabajador' => $numeroTrabajador,
                'peco_nombre' => $nombre,
                'peco_apellido_paterno' => $paterno,
                'peco_apellido_materno' => $materno,
            ]);
            $persona->forceFill(['peco_fecha_ultima_actualizacion' => Carbon::today()->subMonths(2)])->saveQuietly();

            $persona->contactos()->create([
                'cont_id_tipo_contacto' => $celular,
                'cont_descripcion' => $telefonoCelular,
                'cont_es_principal' => true,
            ]);

            if ($telefonoAlterno !== null) {
                $persona->contactos()->create([
                    'cont_id_tipo_contacto' => $casa,
                    'cont_descripcion' => $telefonoAlterno,
                    'cont_es_principal' => false,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: bool}>  $contactos  [tipo, descripción, ¿principal?]
     */
    private function crearProfesor(
        int $rolId,
        string $correo,
        string $numeroTrabajador,
        string $nombre,
        string $apellidoPaterno,
        string $apellidoMaterno,
        Carbon $ultimaActualizacion,
        array $contactos,
    ): Usuario {
        $usuario = Usuario::updateOrCreate(
            ['usua_correo' => $correo],
            [
                'usua_id_rol' => $rolId,
                'usua_nombre' => $nombre,
                'usua_apellido_paterno' => $apellidoPaterno,
                'usua_apellido_materno' => $apellidoMaterno,
                'usua_activo' => true,
            ],
        );

        // Si ya se había corrido el seeder, no se duplican los datos.
        if ($usuario->personas()->exists()) {
            return $usuario;
        }

        $persona = $usuario->personas()->create([
            'peco_numero_trabajador' => $numeroTrabajador,
            'peco_nombre' => $nombre,
            'peco_apellido_paterno' => $apellidoPaterno,
            'peco_apellido_materno' => $apellidoMaterno,
        ]);
        // Se asigna directo para simular una actualización antigua.
        $persona->forceFill(['peco_fecha_ultima_actualizacion' => $ultimaActualizacion])->saveQuietly();

        foreach ($contactos as [$tipo, $descripcion, $principal]) {
            $persona->contactos()->create([
                'cont_id_tipo_contacto' => TipoContacto::where('tico_tipo_contacto', $tipo)->value('tico_id_tipo_contacto'),
                'cont_descripcion' => $descripcion,
                'cont_es_principal' => $principal,
            ]);
        }

        return $usuario;
    }
}
