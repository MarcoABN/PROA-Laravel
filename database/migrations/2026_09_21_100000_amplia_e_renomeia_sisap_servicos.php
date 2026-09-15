<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    /**
     * Todos os serviços de TIE que o escritório agenda, com nomes por extenso no lugar das siglas
     * (INSC EMB, RNV EMB...). "Embarcação · " / "Moto Aquática · " na frente: ordenada por nome, a lista
     * fica agrupada pelo tipo.
     *
     * A descrição é o texto da opção no SISAP. A extensão compara sem acentos, espaços extras e com
     * qualquer traço igual a "-"; aqui ela é gravada sempre com hífen simples.
     */
    private const SERVICOS = [
        ['Embarcação · Inscrição', 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - EMBARCACAO COM COMPRIMENTO IGUAL OU MENOR QUE 12 METROS - INSCRICAO', 'INSC EMB'],
        ['Embarcação · Renovação', 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - RENOVAÇÃO', 'RNV EMB'],
        ['Embarcação · Alteração de Dados', 'TIE - ALTERACAO DE CARACTERISTICAS, ALTERACAO DA RAZAO SOCIAL OU MUDANCA DE ENDERECO DO PROPRIETARIO - EMBARCACAO INSCRITA NAS CP/DL/AG', null],
        ['Embarcação · Cancelamento de Ônus', 'TIE - CANCELAMENTO DO REGISTRO DE ONUS E AVERBACOES - EMBARCACAO INSCRITA NA CP/DL/AG', null],
        ['Embarcação · Transferência de Jurisdição', 'TIE - TRANSFERENCIA DE JURISDICAO DE EMBARCACAO - INSCRITA NA CP/DL/AG', null],
        ['Embarcação · Transferência de Propriedade', 'TIE - TRANSFERENCIA DE PROPRIEDADE DE EMBARCACAO - ESPORTE E RECREIO - INSCRITA NA CP/DL/AG', 'TRANSF EMB'],
        ['Moto Aquática · Inscrição', 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - MOTO AQUATICA - INSCRICAO', 'INSC MOTO'],
        ['Moto Aquática · Renovação', 'TIE (TITULO DE INSCRICAO DE EMBARCACAO) - MOTO AQUATICA - RENOVACAO', 'RNV MOTO'],
        ['Moto Aquática · Transferência de Jurisdição', 'TIE - TRANSFERENCIA DE JURISDICAO DE MOTO AQUATICA - INSCRITA NA CP/DL/AG', null],
        ['Moto Aquática · Transferência de Propriedade', 'TIE - TRANSFERENCIA DE PROPRIEDADE DE MOTO AQUATICA - INSCRITA NA CP/DL/AG', 'TRANSF MOTO'],
    ];

    public function up(): void
    {
        $existentes = DB::table('sisap_servicos')->get()->keyBy(fn($s) => $this->chave($s->descricao_sisap));
        $agora = now();

        foreach (self::SERVICOS as [$sigla, $descricao]) {
            $atual = $existentes->get($this->chave($descricao));

            if ($atual) {
                DB::table('sisap_servicos')->where('id', $atual->id)
                    ->update(['sigla' => $sigla, 'descricao_sisap' => $descricao, 'updated_at' => $agora]);
            } else {
                DB::table('sisap_servicos')->insert([
                    'sigla' => $sigla, 'descricao_sisap' => $descricao, 'ativo' => true, 'created_at' => $agora, 'updated_at' => $agora,
                ]);
            }
        }
    }

    public function down(): void
    {
        $existentes = DB::table('sisap_servicos')->get()->keyBy(fn($s) => $this->chave($s->descricao_sisap));

        foreach (self::SERVICOS as [, $descricao, $siglaAntiga]) {
            $atual = $existentes->get($this->chave($descricao));

            if (!$atual) {
                continue;
            }

            if ($siglaAntiga) {
                DB::table('sisap_servicos')->where('id', $atual->id)->update(['sigla' => $siglaAntiga]);
            } elseif (!DB::table('agendamento_solicitacoes')->where('sisap_servico_id', $atual->id)->exists()) {
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
