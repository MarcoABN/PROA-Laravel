<?php

use App\Models\LancamentoFinanceiro;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Dois ajustes pedidos depois do módulo pronto:
     *
     * 1. "Observações" saiu do formulário. A coluna vai junto porque não havia
     *    nenhum registro preenchido — deixar coluna morta no banco só confunde
     *    quem for ler a tabela depois.
     *
     * 2. Forma de pagamento virou lista fechada (Dinheiro, Pix, Cartão). O que
     *    já estava gravado como texto livre é normalizado para casar com as
     *    opções novas, senão o campo abriria em branco na edição.
     */
    public function up(): void
    {
        $equivalencias = [
            'dinheiro' => LancamentoFinanceiro::PAGAMENTO_DINHEIRO,
            'especie'  => LancamentoFinanceiro::PAGAMENTO_DINHEIRO,
            'espécie'  => LancamentoFinanceiro::PAGAMENTO_DINHEIRO,
            'pix'      => LancamentoFinanceiro::PAGAMENTO_PIX,
            'cartao'   => LancamentoFinanceiro::PAGAMENTO_CARTAO,
            'cartão'   => LancamentoFinanceiro::PAGAMENTO_CARTAO,
        ];

        foreach ($equivalencias as $gravado => $canonico) {
            DB::table('lancamentos_financeiros')
                ->whereRaw('LOWER(TRIM(forma_pagamento)) = ?', [$gravado])
                ->update(['forma_pagamento' => $canonico]);
        }

        // Valor que não corresponde a nenhuma das três opções é preservado: some
        // do formulário, mas continua no banco para não perder informação.

        Schema::table('lancamentos_financeiros', function (Blueprint $table) {
            $table->dropColumn('observacoes');
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos_financeiros', function (Blueprint $table) {
            $table->text('observacoes')->nullable();
        });
    }
};
