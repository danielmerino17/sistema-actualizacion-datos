<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licenciatura', function (Blueprint $table) {
            $table->increments('lice_id_licenciatura');
            $table->string('lice_nombre_licenciatura', 100);
            $table->string('lice_clave', 3)->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenciatura');
    }
};
