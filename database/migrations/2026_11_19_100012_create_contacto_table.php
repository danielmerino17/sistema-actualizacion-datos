<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Cada medio de contacto de una persona (correo, celular, teléfono de casa...).
     *
     * Cambios respecto al diagrama:
     *  - Se quitó cont_id_parentesco (ahora vive en persona_contacto).
     *  - Se agregó cont_fecha_actualizacion: el Figma muestra la
     *    "Última fecha de actualización" de cada dato.
     */
    public function up(): void
    {
        Schema::create('contacto', function (Blueprint $table) {
            $table->increments('cont_id_contacto');
            $table->unsignedInteger('cont_id_persona_contacto');
            $table->unsignedInteger('cont_id_tipo_contacto');
            $table->string('cont_descripcion', 50);
            $table->boolean('cont_es_principal')->default(false);
            $table->string('cont_lada', 5)->nullable();
            $table->string('cont_ext', 5)->nullable();
            $table->timestamp('cont_fecha_actualizacion')->useCurrent();

            $table->foreign('cont_id_persona_contacto')
                ->references('peco_id_persona_contacto')->on('persona_contacto')
                ->cascadeOnDelete();
            $table->foreign('cont_id_tipo_contacto')
                ->references('tico_id_tipo_contacto')->on('tipo_contacto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacto');
    }
};
