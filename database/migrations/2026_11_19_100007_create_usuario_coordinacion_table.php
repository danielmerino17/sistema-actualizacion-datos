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
        Schema::create('usuario_coordinacion', function (Blueprint $table) {
            $table->unsignedInteger('usco_id_coordinacion');
            $table->unsignedInteger('usco_id_usuario');

            $table->primary(['usco_id_coordinacion', 'usco_id_usuario']);

            $table->foreign('usco_id_coordinacion')
                ->references('coor_id_coordinacion')->on('coordinacion')
                ->cascadeOnDelete();
            $table->foreign('usco_id_usuario')
                ->references('usua_id_usuario')->on('usuario')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_coordinacion');
    }
};
