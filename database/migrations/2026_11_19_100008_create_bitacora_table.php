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
        Schema::create('bitacora', function (Blueprint $table) {
            $table->increments('bita_id_bitacora');
            $table->unsignedInteger('bita_id_usuario');
            $table->string('bita_accion', 20);
            // En el diagrama era DATE; TIMESTAMP para guardar también la hora.
            $table->timestamp('bita_fecha_hora')->useCurrent();

            $table->foreign('bita_id_usuario')
                ->references('usua_id_usuario')->on('usuario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
