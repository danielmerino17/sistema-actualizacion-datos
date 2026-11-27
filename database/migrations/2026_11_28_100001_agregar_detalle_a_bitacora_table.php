<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * La bitácora ahora registra:
     *  - bita_id_usuario  (ahora opcional): quién hizo la acción. Vacío en un
     *                     intento de login fallido o en un comando de consola.
     *  - bita_id_profesor (nuevo): a qué profesor afectó (ej. el administrador
     *                     editó o descargó los datos de un profesor).
     *  - bita_detalle     (nuevo): qué registro se tocó, desde qué IP, etc.
     */
    public function up(): void
    {
        Schema::table('bitacora', function (Blueprint $table) {
            $table->unsignedInteger('bita_id_usuario')->nullable()->change();
            $table->unsignedInteger('bita_id_profesor')->nullable()->after('bita_id_usuario');
            $table->string('bita_detalle', 255)->nullable()->after('bita_accion');

            $table->foreign('bita_id_profesor')
                ->references('usua_id_usuario')->on('usuario')
                ->nullOnDelete();
            $table->index('bita_fecha_hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bitacora', function (Blueprint $table) {
            $table->dropIndex(['bita_fecha_hora']);
            $table->dropForeign(['bita_id_profesor']);
            $table->dropColumn(['bita_id_profesor', 'bita_detalle']);
        });
    }
};
