<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;

class DeclaracaoPerdaMotoaquatica implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2C - Perda/Extravio (212)'; }
    public function getTemplatePath(): string { return storage_path('app/templates/Anexo2C-N212.pdf'); }
    public function getFormSchema(): array { return []; }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;
        return [
            'nome' => $this->up($c->nome), 'rg' => $this->up($c->rg),
            'dtemissao' => $c->dt_emissao ? Carbon::parse($c->dt_emissao)->format('d/m/Y') : '',
            'cpf1' => $c->cpfcnpj ?? '', 'telefone' => $c->telefone ?? '', 'celular' => $c->celular ?? '',
            'nacionalidade' => $this->up($c->nacionalidade), 'naturalidade' => $this->up($c->naturalidade), 'email' => $this->up($c->email),
            'nomeinscricaoembarcacao' => $this->up(($embarcacao->nome_embarcacao ?? '') . ', ' . ($embarcacao->num_inscricao ?? '')),
            'localdata' => $this->up($c->cidade ?? 'Brasília') . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}