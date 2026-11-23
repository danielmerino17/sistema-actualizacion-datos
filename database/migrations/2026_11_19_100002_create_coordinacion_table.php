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
        Schema::create('coordinacion', function (Blueprint $table) {
            $table->increments('coor_id_coordinacion');
            $table->unsignedInteger('coor_id_licenciatura');
            $table->string('coor_nombre_coordinacion', 100);

            $table->foreign('coor_id_licenciatura')
                ->references('lice_id_licenciatura')->on('licenciatura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coordinacion');
    }
};
