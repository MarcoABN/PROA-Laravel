<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplifica o agendamento na Marinha para uma tela só.
 *
 * - O procurador passa a ser informado na própria solicitação, que já nasce dentro de um agendamento.
 * - Saem processo, observações e o status da solicitação (o status que vale é o do agendamento).
 * - Os status "planejado" e "pronto" viram "pendente": não há mais etapa de liberar para a extensão.
 *
 * Solicitações que ainda estavam na fila (sem agendamento) não têm de onde tirar o procurador.
 * Nesse caso a migration aborta antes de alterar qualquer coisa — preferimos que alguém
 * decida o procurador a inventar um.
 */
return new class extends Migration {
    public function up(): void
    {
        $semAgendamento = DB::table('agendamento_solicitacoes')
            ->whereNull('agendamento_marinha_id')
            ->pluck('id');

        if ($semAgendamento->isNotEmpty()) {
            throw new RuntimeException(
                'Migration abortada: solicitações sem agendamento não têm procurador definido (ids: '
                . $semAgendamento->implode(', ') . '). Exclua-as ou vincule-as a um agendamento e rode a migration novamente.'
            );
        }

        DB::table('agendamentos_marinha')
            ->whereIn('status', ['planejado', 'pronto'])
            ->update(['status' => 'pendente']);

        // Agendamentos vazios agora são descartados automaticamente; os que sobraram da tela antiga
        // ocupariam a cota do procurador sem ter nenhum serviço.
        DB::table('agendamentos_marinha')
            ->where('status', '!=', 'agendado')
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))
                ->from('agendamento_solicitacoes')
                ->whereColumn('agendamento_solicitacoes.agendamento_marinha_id', 'agendamentos_marinha.id'))
            ->delete();

        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->foreignId('prestador_id')->nullable()->constrained('prestadores');
        });

        DB::statement(<<<'SQL'
            UPDATE agendamento_solicitacoes s
            SET prestador_id = a.prestador_id
            FROM agendamentos_marinha a
            WHERE a.id = s.agendamento_marinha_id
        SQL);

        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->dropIndex(['capitania_id', 'competencia', 'status']);
            $table->dropForeign(['processo_id']);
            $table->dropColumn(['processo_id', 'status', 'observacoes']);

            $table->unsignedBigInteger('prestador_id')->nullable(false)->change();

            // Antes: nulo ao apagar o agendamento. Agora toda solicitação pertence a um agendamento.
            $table->dropForeign(['agendamento_marinha_id']);
            $table->unsignedBigInteger('agendamento_marinha_id')->nullable(false)->change();
            $table->foreign('agendamento_marinha_id')->references('id')->on('agendamentos_marinha');

            $table->index(['prestador_id', 'capitania_id', 'competencia']);
        });

        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->dropIndex(['capitania_id', 'competencia']);
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos_marinha', function (Blueprint $table) {
            $table->index(['capitania_id', 'competencia']);
        });

        Schema::table('agendamento_solicitacoes', function (Blueprint $table) {
            $table->dropIndex(['prestador_id', 'capitania_id', 'competencia']);

            $table->dropForeign(['agendamento_marinha_id']);
            $table->unsignedBigInteger('agendamento_marinha_id')->nullable()->change();
            $table->foreign('agendamento_marinha_id')->references('id')->on('agendamentos_marinha')->nullOnDelete();

            $table->dropForeign(['prestador_id']);
            $table->dropColumn('prestador_id');

            $table->foreignId('processo_id')->nullable()->constrained('processos')->nullOnDelete();
            $table->string('status')->default('alocada');
            $table->text('observacoes')->nullable();

            $table->index(['capitania_id', 'competencia', 'status']);
        });

        DB::table('agendamentos_marinha')
            ->where('status', 'pendente')
            ->update(['status' => 'planejado']);
    }
};
