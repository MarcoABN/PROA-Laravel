<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;

class Anexo1C implements AnexoInterface
{
    public function getTitulo(): string
    {
        return 'Anexo 1C - Declaração de Residência';
    }

    public function getTemplatePath(): string
    {
        // Caminho atualizado para evitar problemas com o git
        return storage_path('app/templates/ANEXO_1_C_N212.docx');
    }

    public function getFormSchema(): array
    {
        return [];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;

        // Formatação do endereço completo em uma única string
        $enderecoCompleto = sprintf(
            '%s, %s, %s, %s, %s, CEP: %s',
            $c->logradouro,
            $c->numero,
            $c->complemento,
            $c->bairro,
            $c->cidade,
            $c->cep
        );

        return [
            'nome' => $this->up($c->nome),
            'cpf' => $this->formatarCpfCnpj($c->cpfcnpj ?? ''),
            'nacionalidade' => $this->up($c->nacionalidade),
            'naturalidade' => $this->up($c->naturalidade),
            'telefone' => $c->telefone ?? '',
            'celular' => $c->celular ?? '',
            'email' => $this->up($c->email),
            'endereço' => $this->up($enderecoCompleto),
            'localdata' => $this->up($c->cidade ?? 'Brasília') . ', ' . Carbon::now()->format('d/m/Y'),
        ];
    }

    private function up($valor)
    {
        return mb_strtoupper((string) ($valor ?? ''), 'UTF-8');
    }

    private function formatarCpfCnpj($valor)
    {
        $valor = preg_replace('/[^0-9]/', '', (string) $valor);
        if (strlen($valor) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
        }
        if (strlen($valor) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
        }
        return $valor;
    }
}