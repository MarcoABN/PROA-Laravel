<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Cliente;
use Carbon\Carbon;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;

class Anexo2E212 implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2E - Autorização Transf. (212)'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2E-N212.pdf'); }

    public function getFormSchema(): array
    {
        return [
            Section::make('Dados do Vendedor')->schema([
                Select::make('buscar_vendedor')->searchable()->options(Cliente::limit(50)->pluck('nome', 'id'))->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($cliente = Cliente::find($state)) { $set('vendedor_nome', $cliente->nome); $set('vendedor_cpf', $cliente->cpfcnpj); }
                    }),
                TextInput::make('vendedor_nome')->required(), TextInput::make('vendedor_cpf')->required(),
            ])
        ];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente; 
        $end = $this->up(($c->logradouro ?? '') . ', ' . ($c->numero ?? '') . ', ' . ($c->complemento ?? '') . ', ' . ($c->bairro ?? '') . ', ' . ($c->cidade ?? '') . ', CEP: ' . ($c->cep ?? ''));

        return [
            'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao), 'inscricao' => $this->up($embarcacao->num_inscricao),
            'nomevendedor' => $this->up($input['vendedor_nome']), 'cpfcnpjvendedor' => $input['vendedor_cpf'] ?? '',
            'nome' => $this->up($c->nome), 'rg' => $this->up($c->rg), 'cpfcnpj' => $c->cpfcnpj ?? '',
            'endereco1' => mb_substr($end, 0, 75), 'endereco2' => mb_substr($end, 75),
            'valor' => 'R$ ' . number_format($embarcacao->valor ?? 0, 2, ',', '.'),
            'localdata' => $this->up($c->cidade ?? 'Brasília') . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}