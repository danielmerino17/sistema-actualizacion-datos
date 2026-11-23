<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Una persona que pertenece a un usuario (el profesor, por peco_id_usuario):
     *  - peco_id_parentesco NULL     => es el propio profesor (sus "Datos personales").
     *  - peco_id_parentesco con valor => es un contacto de emergencia del profesor.
     *
     * Cambios respecto al diagrama:
     *  - peco_id_parentesco se movió aquí desde contacto: en el Figma el
     *    parentesco es de la persona, no de cada teléfono.
     *  - Se quitó usuario.usua_id_persona_contacto para evitar la referencia
     *    circular: los datos del profesor son su persona sin parentesco.
     */
    public function up(): void
    {
        Schema::create('persona_contacto', function (Blueprint $table) {
            $table->increments('peco_id_persona_contacto');
            $table->unsignedInteger('peco_id_usuario');
            $table->unsignedInteger('peco_id_parentesco')->nullable();
            $table->string('peco_numero_trabajador', 7)->index();
            $table->string('peco_nombre', 40);
            $table->string('peco_apellido_paterno', 30);
            $table->string('peco_apellido_materno', 30)->nullable();
            $table->date('peco_fecha_ultima_actualizacion')->nullable();

            $table->foreign('peco_id_usuario')
                ->references('usua_id_usuario')->on('usuario');
            $table->foreign('peco_id_parentesco')
                ->references('pare_id_parentesco')->on('parentesco');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona_contacto');
    }
};
