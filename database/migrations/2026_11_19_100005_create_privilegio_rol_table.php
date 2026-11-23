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
        Schema::create('privilegio_rol', function (Blueprint $table) {
            $table->unsignedInteger('pero_id_privilegio');
            $table->unsignedInteger('pero_id_rol');

            $table->primary(['pero_id_privilegio', 'pero_id_rol']);

            $table->foreign('pero_id_privilegio')
                ->references('priv_id_privilegio')->on('privilegio')
                ->cascadeOnDelete();
            $table->foreign('pero_id_rol')
                ->references('rol_id_rol')->on('rol')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privilegio_rol');
    }
};
