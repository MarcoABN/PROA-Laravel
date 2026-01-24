<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Cliente;
use App\Models\Capitania;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;

class Anexo2K implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2K - Comunicação Transferência'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2K-N211.pdf'); }

    public function getFormSchema(): array
    {
        return [
            Select::make('capitania_id')->label('CP / DL / AG')
                ->options(fn() => Capitania::all()->pluck('sigla', 'id'))
                ->default(fn() => Capitania::where('padrao', true)->first()?->id)->searchable()->preload()->required(),
            Section::make('Dados do Proprietário Anterior')->schema([
                Select::make('buscar_cliente')->label('Buscar Cliente')->searchable()
                    ->options(Cliente::limit(50)->pluck('nome', 'id'))->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($c = Cliente::find($state)) {
                            $set('antigo_nome', $c->nome); $set('antigo_cpf', $c->cpfcnpj);
                            $set('antigo_rg', $c->rg); $set('antigo_orgao', $c->org_emissor);
                            $set('antigo_dtexp', $c->dt_emissao); $set('antigo_cep', $c->cep);
                            $set('antigo_endereco', $c->logradouro); $set('antigo_numero', $c->numero);
                            $set('antigo_complemento', $c->complemento); $set('antigo_bairro', $c->bairro);
                            $set('antigo_cidade', $c->cidade); $set('antigo_uf', $c->uf);
                        }
                    }),
                TextInput::make('antigo_nome')->required(),
                Grid::make(2)->schema([TextInput::make('antigo_cpf')->required(), TextInput::make('antigo_rg'), TextInput::make('antigo_orgao'), DatePicker::make('antigo_dtexp')]),
                Grid::make(3)->schema([
                    TextInput::make('antigo_cep'), TextInput::make('antigo_endereco')->columnSpan(2), TextInput::make('antigo_numero'),
                    TextInput::make('antigo_complemento'), TextInput::make('antigo_bairro'), TextInput::make('antigo_cidade'), TextInput::make('antigo_uf'),
                ]),
            ])->collapsible(),
        ];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;
        $capitania = Capitania::find($input['capitania_id']);

        return [
            'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao),
            'inscricao' => $this->up($embarcacao->num_inscricao),
            'cp-dl-ag' => $capitania ? $this->up($capitania->sigla) : '',
            
            // CORREÇÃO AQUI: Adicionado "?? ''" em todos os campos opcionais do input
            'nomeproprietarioanterior' => $this->up($input['antigo_nome'] ?? ''),
            'rg' => $this->up($input['antigo_rg'] ?? ''),
            'orgaoexpedidor' => $this->up($input['antigo_orgao'] ?? ''),
            'dtexpedicao' => !empty($input['antigo_dtexp']) ? Carbon::parse($input['antigo_dtexp'])->format('d/m/Y') : '',
            'cpfcnpj' => $input['antigo_cpf'] ?? '',
            'endereco' => $this->up($input['antigo_endereco'] ?? ''),
            'numero' => $input['antigo_numero'] ?? '',
            'complemento' => $this->up($input['antigo_complemento'] ?? ''),
            'bairro' => $this->up($input['antigo_bairro'] ?? ''),
            'cidade' => $this->up($input['antigo_cidade'] ?? ''),
            'uf' => $this->up($input['antigo_uf'] ?? ''),
            'cep' => $input['antigo_cep'] ?? '',
            
            'nomenovoproprietario' => $this->up($c->nome),
            'rgnovoproprietario' => $this->up($c->rg),
            'orgaoexpedidornovoproprietario' => $this->up($c->org_emissor),
            'dtexpedicaonovoproprietario' => $c->dt_emissao ? Carbon::parse($c->dt_emissao)->format('d/m/Y') : '',
            'cpfcnpjnovoproprietario' => $c->cpfcnpj ?? '',
            'endereconovoproprietario' => $this->up($c->logradouro),
            'numeronovoproprietario' => $c->numero ?? '',
            'complementonovoproprietario' => $this->up($c->complemento),
            'bairronovoproprietario' => $this->up($c->bairro),
            'cidadenovoproprietario' => $this->up($c->cidade),
            'ufnovoproprietario' => $this->up($c->uf),
            'cepnovoproprietario' => $c->cep ?? '',
            
            'localdata' => $this->up($embarcacao->cidade ?? 'Brasília') . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];
    }
    
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}