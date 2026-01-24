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
        // Tabela Principal: Processos
        Schema::create('processos', function (Blueprint $table) {
            $table->id();

            // Vínculos
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('embarcacao_id')->nullable()->constrained('embarcacoes')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Dono do processo

            // Dados do Serviço
            $table->string('titulo'); // Ex: Transferência de Propriedade
            $table->string('status')->default('triagem'); // triagem, analise, exigencia, concluido...
            $table->string('prioridade')->default('normal'); // normal, urgente
            $table->date('prazo_estimado')->nullable();

            $table->timestamps();
        });

        // Tabela Secundária: Histórico (Timeline)
        Schema::create('processo_andamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('processo_id')->constrained('processos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Quem fez o andamento

            $table->text('descricao'); // O que aconteceu
            $table->string('tipo')->default('comentario'); // comentario, mudanca_status, anexo

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processos');
    }
};
