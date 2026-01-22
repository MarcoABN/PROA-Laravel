<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;

class Anexo1C implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 1C - Declaração de Residência'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo1C-N212.pdf'); }
    public function getFormSchema(): array { return []; }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;
        $end = $this->up(sprintf('%s, %s, %s, %s, %s, CEP: %s', $c->logradouro, $c->numero, $c->complemento, $c->bairro, $c->cidade, $c->cep));

        return [
            'nome' => $this->up($c->nome), 'nacionalidade' => $this->up($c->nacionalidade),
            'naturalidade' => $this->up($c->naturalidade), 'cpf' => $c->cpfcnpj ?? '',
            'telefone' => $c->telefone ?? '', 'celular' => $c->celular ?? '', 'email' => $this->up($c->email),
            'endereco1' => mb_substr($end, 0, 85), 'endereco2' => mb_substr($end, 85),
            'localdata' => $this->up($c->cidade ?? 'Brasília') . ', ' . Carbon::now()->format('d/m/Y'),
        ];
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}