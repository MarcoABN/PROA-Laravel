<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;

class Procuracao implements AnexoInterface
{
    public function getTitulo(): string { return 'Procuração - Campeão Despachante'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Procuracao01OR.docx'); }
    public function getFormSchema(): array { return []; }

    public function getDados($record, array $input): array
    {
        Carbon::setLocale('pt_BR');
        // Suporte hibrido: Aceita Embarcacao (via TIE) ou Cliente (via botao direto)
        $c = ($record instanceof \App\Models\Embarcacao) ? $record->cliente : $record;

        $endereco = ($c->logradouro ?? '') . ', ' . ($c->numero ?? '') . (!empty($c->complemento) ? ' - ' . $c->complemento : '');

        return [
            'nomecliente1'    => $this->up($c->nome),
            'enderecocliente' => $this->up($endereco),
            'cep'             => $c->cep ?? '',
            'cidade'          => $this->up($c->cidade . '/' . $c->uf),
            'bairro'          => $this->up($c->bairro),
            'rg'              => $this->up($c->rg),
            'orgexpedidor'    => $this->up($c->org_emissor),
            'cpfcliente1'     => $c->cpfcnpj ?? '',
            'email'           => $this->up($c->email),
            'celular'         => $c->celular ?? $c->telefone ?? '',
            'nomecliente2'    => $this->up($c->nome),
        ];
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}