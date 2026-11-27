<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CatalogosSeeder::class,
            RolesSeeder::class,
        ]);

        // Usuarios inventados para trabajar sin bd_fca (solo con accesos de prueba).
        if (app()->isLocal() && config('sacpa.accesos_prueba')) {
            $this->call(DesarrolloSeeder::class);
        }
    }
}
