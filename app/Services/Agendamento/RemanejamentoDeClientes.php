<?php

namespace App\Services\Agendamento;

use App\Models\AgendamentoMarinha;
use App\Models\SolicitacaoAgendamento;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Quando o SISAP recusa um cliente durante o agendamento, a vaga dele não pode ficar queimada:
 * um cliente do outro agendamento ainda não marcado do mesmo procurador (mesma capitania e mês)
 * vem para o lugar dele, e o descartado vai para esse outro agendamento. Vale nos dois sentidos:
 * se o 2º for feito antes, quem vem é um cliente do 1º. Assim o agendamento em andamento sai
 * completo e sobra tempo para corrigir o descartado antes do outro.
 */
class RemanejamentoDeClientes
{
    /**
     * @param  Collection<int, SolicitacaoAgendamento>  $descartadas  já marcadas como descartadas, deste agendamento
     * @return Collection<int, SolicitacaoAgendamento>  os substitutos, já neste agendamento
     */
    public static function trocarDescartados(AgendamentoMarinha $agendamento, Collection $descartadas): Collection
    {
        return DB::transaction(function () use ($agendamento, $descartadas) {
            $substitutos = collect();

            foreach ($descartadas as $descartada) {
                $outro = static::outroAgendamento($agendamento);

                if (!$outro) {
                    break;
                }

                // Quem já está neste agendamento não sobe de novo: no SISAP a pessoa aparece uma vez só.
                $documentosAqui = $agendamento->solicitacoes()->whereNull('descartada_em')->pluck('cliente_documento');

                $substituto = $outro->solicitacoes()
                    ->whereNull('descartada_em')
                    ->whereNotIn('cliente_documento', $documentosAqui)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (!$substituto) {
                    break;
                }

                $substituto->update(['agendamento_marinha_id' => $agendamento->id]);
                $descartada->update(['agendamento_marinha_id' => $outro->id]);

                $substitutos->push($substituto->load('servico'));
            }

            return $substitutos;
        });
    }

    /**
     * O outro agendamento do mesmo procurador, capitania e mês que ainda não foi marcado no SISAP,
     * antes ou depois deste na ordem (com mais de um, o de ordem mais próxima).
     */
    private static function outroAgendamento(AgendamentoMarinha $agendamento): ?AgendamentoMarinha
    {
        return AgendamentoMarinha::query()
            ->whereKeyNot($agendamento->getKey())
            ->where('prestador_id', $agendamento->prestador_id)
            ->where('capitania_id', $agendamento->capitania_id)
            ->whereDate('competencia', $agendamento->competencia->toDateString())
            ->whereIn('status', [AgendamentoMarinha::STATUS_PENDENTE, AgendamentoMarinha::STATUS_FALHOU])
            ->orderByRaw('abs(ordem - ?)', [$agendamento->ordem])
            ->orderBy('ordem')
            ->lockForUpdate()
            ->first();
    }
}
