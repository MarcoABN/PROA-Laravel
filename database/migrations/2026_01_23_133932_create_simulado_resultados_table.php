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
        Schema::create('simulado_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // ID do cliente logado
            $table->string('modalidade');
            $table->integer('acertos');
            $table->integer('total');
            $table->decimal('porcentagem', 5, 2);
            $table->boolean('aprovado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulado_resultados');
    }
};
