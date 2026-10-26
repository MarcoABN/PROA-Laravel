<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Serviços de CHA (Arrais, Mestre e Capitão-Amador). "CHA Amador · " na frente para a lista ficar
     * agrupada, como "Embarcação · " e "Moto Aquática · ". A descrição é o texto da opção no SISAP.
     */
    private const SERVICOS = [
        ['CHA Amador · Inscrição e Emissão', 'CARTEIRA DE HABILITACAO DE AMADOR (CHA) - INSCRICAO PARA O EXAME E EMISSAO PARA ARRAIS-AMADOR, MESTRE-AMADOR E CAPITAO-AMADOR'],
        ['CHA Amador · Renovação', 'CARTEIRA DE HABILITACAO DE AMADOR (CHA) - RENOVACAO PARA ARRAIS-AMADOR, MESTRE-AMADOR E CAPITAO-AMADOR'],
    ];

    public function up(): void
    {
        $existentes = DB::table('sisap_servicos')->get()->keyBy(fn($s) => $this->chave($s->descricao_sisap));
        $agora = now();

        foreach (self::SERVICOS as [$sigla, $descricao]) {
            if ($existentes->has($this->chave($descricao))) {
                continue;
            }

            DB::table('sisap_servicos')->insert([
                'sigla' => $sigla, 'descricao_sisap' => $descricao, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora,
            ]);
        }
    }

    public function down(): void
    {
        $existentes = DB::table('sisap_servicos')->get()->keyBy(fn($s) => $this->chave($s->descricao_sisap));

        foreach (self::SERVICOS as [, $descricao]) {
            $atual = $existentes->get($this->chave($descricao));

            if ($atual && !DB::table('agendamento_solicitacoes')->where('sisap_servico_id', $atual->id)->exists()) {
                DB::table('sisap_servicos')->where('id', $atual->id)->delete();
            }
        }
    }

    /** Mesma comparação da extensão (lib.js normalizar). */
    private function chave(string $texto): string
    {
        $texto = preg_replace('/[\x{2010}-\x{2015}\x{2212}]/u', '-', $texto);

        return Str::upper(Str::squish(Str::ascii($texto)));
    }
};
