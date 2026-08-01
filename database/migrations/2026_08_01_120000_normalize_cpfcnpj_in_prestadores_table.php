<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Padroniza prestadores.cpfcnpj para conter apenas dígitos, alinhando com o
 * cadastro de Clientes (que já grava sem máscara).
 *
 * Só normaliza documentos com 11 (CPF) ou 14 (CNPJ) dígitos. Qualquer registro
 * fora desse padrão é deixado intacto para inspeção manual — preferimos manter
 * o dado original a "consertar" algo que não entendemos.
 */
return new class extends Migration {
    public function up(): void
    {
        // Trava de segurança: se dois registros diferentes virarem o mesmo documento
        // após remover a máscara, a UNIQUE explodiria no meio do UPDATE.
        // Abortamos antes de tocar em qualquer linha.
        $colisoes = DB::table('prestadores')
            ->selectRaw("REGEXP_REPLACE(cpfcnpj, '[^0-9]', '', 'g') as limpo, COUNT(*) as total, STRING_AGG(id::text, ',') as ids")
            ->groupBy('limpo')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($colisoes->isNotEmpty()) {
            $detalhe = $colisoes
                ->map(fn($c) => "{$c->limpo} (ids: {$c->ids})")
                ->implode('; ');

            throw new RuntimeException(
                "Migration abortada: normalizar cpfcnpj criaria duplicatas em prestadores -> {$detalhe}. " .
                'Resolva os registros duplicados manualmente e rode a migration novamente.'
            );
        }

        DB::statement(<<<'SQL'
            UPDATE prestadores
            SET cpfcnpj = REGEXP_REPLACE(cpfcnpj, '[^0-9]', '', 'g')
            WHERE cpfcnpj ~ '[^0-9]'
              AND LENGTH(REGEXP_REPLACE(cpfcnpj, '[^0-9]', '', 'g')) IN (11, 14)
        SQL);
    }

    public function down(): void
    {
        // Reaplica a máscara: CPF 999.999.999-99 / CNPJ 99.999.999/9999-99
        DB::statement(<<<'SQL'
            UPDATE prestadores
            SET cpfcnpj = REGEXP_REPLACE(cpfcnpj, '(\d{3})(\d{3})(\d{3})(\d{2})', '\1.\2.\3-\4')
            WHERE cpfcnpj ~ '^\d{11}$'
        SQL);

        DB::statement(<<<'SQL'
            UPDATE prestadores
            SET cpfcnpj = REGEXP_REPLACE(cpfcnpj, '(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})', '\1.\2.\3/\4-\5')
            WHERE cpfcnpj ~ '^\d{14}$'
        SQL);
    }
};
