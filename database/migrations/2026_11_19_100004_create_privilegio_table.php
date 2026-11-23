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
        Schema::create('privilegio', function (Blueprint $table) {
            $table->increments('priv_id_privilegio');
            $table->string('priv_tipo_privilegio', 20)->unique();
            $table->string('priv_descripcion', 80)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privilegio');
    }
};
