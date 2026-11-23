<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Usuarios del sistema: profesores y administradores (según su rol).
     *
     * Nota: el diagrama tenía también usua_id_persona_contacto, pero
     * persona_contacto ya apunta a usuario (peco_id_usuario). Se deja
     * una sola referencia para evitar la relación circular.
     */
    public function up(): void
    {
        Schema::create('usuario', function (Blueprint $table) {
            $table->increments('usua_id_usuario');
            $table->unsignedInteger('usua_id_rol');
            $table->string('usua_nombre', 40);
            $table->string('usua_apellido_paterno', 30);
            $table->string('usua_apellido_materno', 30)->nullable();
            $table->string('usua_correo', 100)->unique();
            $table->boolean('usua_activo')->default(true);

            $table->foreign('usua_id_rol')
                ->references('rol_id_rol')->on('rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario');
    }
};
