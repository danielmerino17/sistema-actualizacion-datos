<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos fijos indicados en el diagrama de la base de datos.
 * Se puede correr varias veces sin duplicar registros.
 */
class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $parentescos = ['Madre', 'Padre', 'Hijo(a)', 'Hermano(a)', 'Esposo(a)', 'Otro'];

        foreach ($parentescos as $parentesco) {
            DB::table('parentesco')->updateOrInsert(['pare_parentesco' => $parentesco]);
        }

        $tiposContacto = ['Casa', 'Oficina', 'Celular', 'Trabajo', 'Correo'];

        foreach ($tiposContacto as $tipo) {
            DB::table('tipo_contacto')->updateOrInsert(['tico_tipo_contacto' => $tipo]);
        }
    }
}
