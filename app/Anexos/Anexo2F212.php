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

class Anexo2F212 implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2F - Comunicação Transf. (212)'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2F-N212.pdf'); }

    public function getFormSchema(): array
    {
        return [
            Select::make('capitania_id')->label('CP / DL / AG')->options(fn() => Capitania::all()->pluck('sigla', 'id'))
                ->default(fn() => Capitania::where('padrao', true)->first()?->id)->searchable()->preload()->required(),
            Section::make('Proprietário Anterior (Vendedor)')->schema([
                Select::make('buscar_anterior')->searchable()->options(Cliente::limit(50)->pluck('nome', 'id'))->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($c = Cliente::find($state)) {
                            $set('antigo_nome', $c->nome); $set('antigo_cpf', $c->cpfcnpj); $set('antigo_rg', $c->rg); $set('antigo_orgao', $c->org_emissor);
                            $set('antigo_dtexp', $c->dt_emissao); $set('antigo_cep', $c->cep); $set('antigo_endereco', $c->logradouro); $set('antigo_numero', $c->numero);
                            $set('antigo_complemento', $c->complemento); $set('antigo_bairro', $c->bairro); $set('antigo_cidade', $c->cidade); $set('antigo_uf', $c->uf);
                        }
                    }),
                TextInput::make('antigo_nome')->required(),
                Grid::make(3)->schema([TextInput::make('antigo_cpf')->required(), TextInput::make('antigo_rg'), TextInput::make('antigo_orgao')]),
                Grid::make(3)->schema([DatePicker::make('antigo_dtexp'), TextInput::make('antigo_cep'), TextInput::make('antigo_uf')]),
                Grid::make(2)->schema([TextInput::make('antigo_endereco'), TextInput::make('antigo_numero')]),
                Grid::make(3)->schema([TextInput::make('antigo_complemento'), TextInput::make('antigo_bairro'), TextInput::make('antigo_cidade')]),
            ])->collapsible(),
        ];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $novo = $embarcacao->cliente;
        $dtExpNovo = $novo->dt_emissao ? Carbon::parse($novo->dt_emissao)->format('d/m/Y') : '';
        $dtExpAntigo = !empty($input['antigo_dtexp']) ? Carbon::parse($input['antigo_dtexp'])->format('d/m/Y') : '';
        $capitania = Capitania::find($input['capitania_id']);

        return [
            'nomemotoaquatica' => $this->up($embarcacao->nome_embarcacao), 'inscricao' => $this->up($embarcacao->num_inscricao),
            'cp-dl-ag' => $capitania ? $this->up($capitania->sigla) : '',
            
            // CORREÇÃO: Adicionado "?? ''" para todos os campos do proprietário anterior
            'nomeproprietarioanterior' => $this->up($input['antigo_nome'] ?? ''), 
            'docidentidadeproprietarioanterior' => $this->up($input['antigo_rg'] ?? ''),
            'orgaoexpedidorproprietarioanterior' => $this->up($input['antigo_orgao'] ?? ''), 
            'dataexpedicaoproprietarioanterior' => $dtExpAntigo,
            'cpfcnpjproprietarioanterior' => $input['antigo_cpf'] ?? '', 
            'enderecoproprietarioanterior' => $this->up($input['antigo_endereco'] ?? ''),
            'numeroproprietarioanterior' => $input['antigo_numero'] ?? '', 
            'complementoproprietarioanterior' => $this->up($input['antigo_complemento'] ?? ''),
            'bairroproprietarioanterior' => $this->up($input['antigo_bairro'] ?? ''), 
            'cidadeproprietarioanterior' => $this->up($input['antigo_cidade'] ?? ''),
            'ufproprietarioanterior' => $this->up($input['antigo_uf'] ?? ''), 
            'cepproprietarioanterior' => $input['antigo_cep'] ?? '',
            
            'nomenovoproprietario' => $this->up($novo->nome), 'docidentidadenovoproprietario' => $this->up($novo->rg),
            'orgaoexpedidornovoproprietario' => $this->up($novo->org_emissor), 'dataexpedicaonovoproprietario' => $dtExpNovo,
            'cpfcnpjnovoproprietario' => $novo->cpfcnpj ?? '', 'endereconovoproprietario' => $this->up($novo->logradouro),
            'numeronovoproprietario' => $novo->numero ?? '', 'complementonovoproprietario' => $this->up($novo->complemento),
            'bairronovoproprietario' => $this->up($novo->bairro), 'cidadenovoproprietario' => $this->up($novo->cidade),
            'ufnovoproprietario' => $this->up($novo->uf), 'cepnovoproprietario' => $novo->cep ?? '',
            'local' => $this->up($novo->cidade ?? 'Brasília'), 'dia' => Carbon::now()->format('d'),
            'mes' => $this->up(Carbon::now()->translatedFormat('F')), 'ano' => Carbon::now()->format('Y'),
        ];
    }
    
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}