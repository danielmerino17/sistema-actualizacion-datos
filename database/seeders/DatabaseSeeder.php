<?php

namespace Database\Seeders;

use App\Models\User;
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

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
