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
        Schema::create('questoes', function (Blueprint $table) {
            $table->id();
            $table->string('categoria'); // Arrais Amador, Motonauta, Mestre, etc.
            $table->string('assunto')->nullable(); // RIPEAM, Combate a Incêndio, Balizamento
            $table->text('enunciado');
            $table->string('imagem')->nullable(); // Caminho da foto da sinalização

            // Alternativas
            $table->text('alternativa_a');
            $table->text('alternativa_b');
            $table->text('alternativa_c');
            $table->text('alternativa_d');
            $table->text('alternativa_e')->nullable(); // Algumas bancas usam até 'E'

            $table->char('resposta_correta', 1); // Armazena 'a', 'b', 'c', etc.
            $table->text('explicacao')->nullable(); // Dica técnica para o aluno aprender

            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questoes');
    }
};
