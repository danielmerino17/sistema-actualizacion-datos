<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

/**
 * Roles del sistema. Pendiente de confirmar con el equipo si hay más
 * (por ejemplo, Coordinador).
 */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        Rol::updateOrCreate(
            ['rol_nombre_rol' => Rol::PROFESOR],
            ['rol_descripcion' => 'Consulta y actualiza sus datos personales y de emergencia'],
        );

        Rol::updateOrCreate(
            ['rol_nombre_rol' => Rol::ADMINISTRADOR],
            ['rol_descripcion' => 'Consulta los datos de los profesores y descarga reportes'],
        );
    }
}
