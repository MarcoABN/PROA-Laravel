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
        Schema::create('propostas', function (Blueprint $table) {
            $table->id();
            // Relacionamentos
            $table->foreignId('escola_nautica_id')->constrained('escola_nauticas');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('embarcacao_id')->nullable()->constrained('embarcacoes');

            // Dados da Proposta
            $table->date('data_proposta');
            $table->integer('sequencial_diario')->default(1); // Para gerar o número 001, 002...

            // Valores
            $table->json('itens_servico')->nullable(); // Guardará array com descrição e valor
            $table->decimal('valor_desconto', 10, 2)->default(0);

            // Status (Rascunho, Gerada, Aceita)
            $table->string('status')->default('rascunho');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('propostas');
    }
};
