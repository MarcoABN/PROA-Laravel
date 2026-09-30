<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Cliente que o SISAP recusou durante o agendamento (GRU já utilizada, documento inválido...).
     * A extensão tira o cliente da tela e segue com os demais; ele fica no cadastro, marcado, até ser
     * corrigido (nova GRU/documento/serviço) ou excluído.
     */
    public function up(): void
    {
        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->timestamp('descartada_em')->nullable();
            $table->text('motivo_descarte')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->dropColumn(['descartada_em', 'motivo_descarte']);
        });
    }
};
