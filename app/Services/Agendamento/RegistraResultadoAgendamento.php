<?php

namespace App\Services\Agendamento;

use App\Models\AgendamentoMarinha;
use Carbon\Carbon;

/**
 * Grava a confirmação do SISAP, vinda da extensão ou digitada à mão.
 * Vale para o agendamento inteiro (todos os serviços do grupo).
 */
class RegistraResultadoAgendamento
{
    public static function registrar(
        AgendamentoMarinha $agendamento,
        string $numero,
        string $chave,
        Carbon $dataHora,
        ?string $sisapId = null,
        ?string $comprovanteLink = null,
    ): void {
        $agendamento->update([
            'status' => AgendamentoMarinha::STATUS_AGENDADO,
            'numero' => $numero,
            'chave' => $chave,
            'data_hora' => $dataHora,
            'sisap_id' => $sisapId,
            'comprovante_link' => $comprovanteLink,
            'erro' => null,
        ]);
    }
}
