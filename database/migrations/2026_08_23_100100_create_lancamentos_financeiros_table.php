<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Lançamentos de entrada e saída.
     *
     * Cada lançamento pertence a um escopo: 'usuario' (o fluxo pessoal de alguém
     * da equipe, com user_id preenchido) ou 'empresa' (gasto/receita da empresa,
     * sem dono). Manter o escopo explícito em vez de deduzir por user_id nulo
     * evita que um lançamento salvo sem usuário vire "da empresa" por acidente.
     */
    public function up(): void
    {
        Schema::create('lancamentos_financeiros', function (Blueprint $table) {
            $table->id();

            $table->string('tipo');   // entrada | saida
            $table->string('escopo'); // usuario | empresa

            // Dono do lançamento. Nulo quando escopo = empresa.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('categoria_financeira_id')->constrained('categorias_financeiras');

            $table->string('descricao');
            $table->decimal('valor', 12, 2);
            $table->date('data_lancamento');
            $table->string('forma_pagamento')->nullable();
            $table->text('observacoes')->nullable();

            // Comprovantes (PDF/imagem). Array de caminhos gravado pelo FileUpload.
            $table->json('anexos')->nullable();

            // Quem registrou — nem sempre é o dono do lançamento.
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('data_lancamento');
            $table->index(['tipo', 'data_lancamento']);
            $table->index(['escopo', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_financeiros');
    }
};
