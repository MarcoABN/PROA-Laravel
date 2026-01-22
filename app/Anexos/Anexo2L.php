<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;

class Anexo2L implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2L - Declaração de Residência'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2L-N211.pdf'); }
    public function getFormSchema(): array { return []; }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $cliente = $embarcacao->cliente;
        
        $enderecoCompleto = $this->up(sprintf('%s, %s, %s, %s, %s, CEP: %s',
            $cliente->logradouro, $cliente->numero, $cliente->complemento, $cliente->bairro, $cliente->cidade, $cliente->cep
        ));

        return [
            'nome' => $this->up($cliente->nome), 'nacionalidade' => $this->up($cliente->nacionalidade),
            'naturalidade' => $this->up($cliente->naturalidade), 'cpf' => $cliente->cpfcnpj ?? '',
            'telefone' => $cliente->telefone ?? '', 'celular' => $cliente->celular ?? '', 'email' => $this->up($cliente->email),
            'endereco1' => mb_substr($enderecoCompleto, 0, 100), 'endereco2' => mb_substr($enderecoCompleto, 100),
            'localdata' => $this->up($cliente->cidade ?? 'Brasília') . ', ' . Carbon::now()->format('d/m/Y'),
        ];
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}