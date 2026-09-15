<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * A GRU não pode repetir no mesmo mês, mas pode voltar num agendamento de outro mês
     * (processo não atendido) ou depois que o agendamento é excluído.
     */
    public function up(): void
    {
        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->dropUnique(['gru']);
            $table->unique(['competencia', 'gru']);
        });
    }

    public function down(): void
    {
        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->dropUnique(['competencia', 'gru']);
            $table->unique('gru');
        });
    }
};
