<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('capitanias', function (Blueprint $table) {
            $table->id();
            $table->string('nome');  // Ex: Capitania Fluvial de Brasília
            $table->string('sigla'); // Ex: CFB
            $table->string('uf', 2); // Ex: DF
            $table->boolean('padrao')->default(false); // Define se é a pré-selecionada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capitanias');
    }
};
