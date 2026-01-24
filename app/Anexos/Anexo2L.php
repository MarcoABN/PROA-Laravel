<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Cliente;
use App\Models\Embarcacao;
use Carbon\Carbon;

class Anexo2L implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2L - Declaração de Residência'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2L-N211.pdf'); }
    public function getFormSchema(): array { return []; }

    public function getDados($record, array $input): array
    {
        Carbon::setLocale('pt_BR');
        
        // LÓGICA HÍBRIDA: Funciona com Embarcação OU Cliente
        if ($record instanceof Embarcacao) {
            // Fluxo TIE (Vem da aba de Embarcações)
            $embarcacao = $record;
            $cliente = $embarcacao->cliente;
            $dadosEmbarcacao = [
                'nome_embarcacao' => $this->up($embarcacao->nome_embarcacao),
                'inscricao' => $this->up($embarcacao->num_inscricao),
            ];
        } elseif ($record instanceof Cliente) {
            // Fluxo CHA (Vem da aba de Habilitação - Apenas Cliente)
            $cliente = $record;
            $dadosEmbarcacao = [
                'nome_embarcacao' => '-----',
                'inscricao' => '-----',
            ];
        } else {
            // Fallback de segurança (tenta pegar como array ou objeto genérico se não for model)
            $cliente = $record; 
            $dadosEmbarcacao = ['nome_embarcacao' => '-----', 'inscricao' => '-----'];
        }
        
        // Formata endereço completo
        $enderecoCompleto = $this->up(sprintf('%s, %s, %s, %s, %s, CEP: %s',
            $cliente->logradouro, 
            $cliente->numero, 
            $cliente->complemento, 
            $cliente->bairro, 
            $cliente->cidade, 
            $cliente->cep
        ));

        // Mescla dados do cliente com dados da embarcação (ou traços)
        return array_merge([
            'nome' => $this->up($cliente->nome), 
            'nacionalidade' => $this->up($cliente->nacionalidade),
            'naturalidade' => $this->up($cliente->naturalidade), 
            'cpf' => $cliente->cpfcnpj ?? '',
            'telefone' => $cliente->telefone ?? '', 
            'celular' => $cliente->celular ?? '', 
            'email' => $this->up($cliente->email),
            'endereco1' => mb_substr($enderecoCompleto, 0, 100), 
            'endereco2' => mb_substr($enderecoCompleto, 100),
            'localdata' => $this->up($cliente->cidade ?? 'Brasília') . ', ' . Carbon::now()->format('d/m/Y'),
        ], $dadosEmbarcacao);
    }

    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}