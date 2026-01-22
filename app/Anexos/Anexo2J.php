<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;
use Filament\Forms\Components\Radio;

class Anexo2J implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2J - Declaração de Perda/Extravio'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2J-N211.pdf'); }

    public function getFormSchema(): array
    {
        return [Radio::make('solicitacao')->label('Documento Perdido/Extraviado')
            ->options(['tie' => 'Título de Inscrição de Embarcação (TIE)', 'tiem' => 'Título de Inscrição de Embarcação Miúda (TIEM)'])
            ->required()];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        $cliente = $embarcacao->cliente;

        $dados = [
            'nome' => $this->up($cliente->nome), 'rg' => $this->up($cliente->rg),
            'dtemissao' => $cliente->dt_emissao ? Carbon::parse($cliente->dt_emissao)->format('d/m/Y') : '',
            'cpf1' => $cliente->cpfcnpj ?? '', 'telefone' => $this->up($cliente->telefone),
            'celular' => $this->up($cliente->celular), 'email' => $this->up($cliente->email),
            'nacionalidade' => $this->up($cliente->nacionalidade), 'naturalidade' => $this->up($cliente->naturalidade),
            'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao), 'inscricao' => $this->up($embarcacao->num_inscricao),
        ];

        $solicitacao = $input['solicitacao'] ?? '';
        if ($solicitacao === 'tie') $dados['check_tie'] = 'Sim';
        if ($solicitacao === 'tiem') $dados['check_tiem'] = 'Sim';

        return $dados;
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}