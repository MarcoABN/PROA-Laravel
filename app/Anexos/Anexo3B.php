<?php
namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\EscolaNautica;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;

class Anexo3B implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 3B - Atestado Motonauta'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo3B-N212.pdf'); }

    public function getFormSchema(): array
    {
        return [
            Section::make('Dados do Treinamento')->schema([
                DatePicker::make('data_aula')->label('Data da Aula')->default(now())->required(),
            ]),
            Section::make('Escola Náutica e Instrutor')->schema([
                Select::make('escola_id')->label('Selecionar Escola')
                    ->options(EscolaNautica::all()->pluck('razao_social', 'id'))->searchable()->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($escola = EscolaNautica::with(['instrutor', 'responsavel'])->find($state)) {
                            $set('escola_nome', $escola->razao_social);
                            if ($escola->instrutor) {
                                $set('instrutor_nome', $escola->instrutor->nome);
                                $set('instrutor_cha', $escola->instrutor->cha_numero ?? '');
                                $set('instrutor_cat', $escola->instrutor->cha_categoria ?? '');
                            }
                            if ($escola->responsavel) {
                                $set('resp_nome', $escola->responsavel->nome);
                                $set('resp_cpf', $escola->responsavel->cpfcnpj);
                                $set('resp_rg', $escola->responsavel->rg ?? '');
                                $set('resp_cha', $escola->responsavel->cha_numero ?? '');
                                $set('resp_org_emissor', $escola->responsavel->org_emissor ?? '');
                                $set('resp_dt_emissao', $escola->responsavel->dt_emissao ?? '');
                            }
                        }
                    })->columnSpanFull(),
                TextInput::make('escola_nome')->label('Estabelecimento')->required(),
                Grid::make(2)->schema([TextInput::make('instrutor_nome'), TextInput::make('instrutor_cha'), TextInput::make('instrutor_cat')]),
                Grid::make(2)->schema([
                    TextInput::make('resp_nome'), TextInput::make('resp_cpf'), TextInput::make('resp_rg'),
                    TextInput::make('resp_org_emissor'), DatePicker::make('resp_dt_emissao')->format('d/m/Y'), TextInput::make('resp_cha')
                ]),
            ])->collapsible(),
        ];
    }

    public function getDados($record, array $input): array
    {
        Carbon::setLocale('pt_BR');
        $c = ($record instanceof \App\Models\Embarcacao) ? $record->cliente : $record;
        $dataAula = isset($input['data_aula']) ? Carbon::parse($input['data_aula']) : Carbon::now();

        return [
            'nomecliente' => mb_strtoupper($c->nome ?? ''), 'cpfcliente' => $c->cpfcnpj ?? '',
            'nomecliente2' => mb_strtoupper($c->nome ?? ''), 'cpfcliente2' => $c->cpfcnpj ?? '',
            'rgcliente' => $c->rg ?? '', 'orgemissorcliente' => $c->org_emissor ?? '',
            'estabelecimento' => mb_strtoupper($input['escola_nome'] ?? ''),
            'nomeresponsavel' => mb_strtoupper($input['resp_nome'] ?? ''),
            'rgresponsavel' => $input['resp_rg'] ?? '', 'cpfresponsavel' => $input['resp_cpf'] ?? '',
            'charesponsavel' => $input['resp_cha'] ?? '',
            'orgemresponsavel' => mb_strtoupper($input['resp_org_emissor'] ?? ''),
            'dtemissaoresponsavel' => isset($input['resp_dt_emissao']) ? Carbon::parse($input['resp_dt_emissao'])->format('d/m/Y') : '',
            'instrutor' => mb_strtoupper($input['instrutor_nome'] ?? ''),
            'nomeinstrutor' => mb_strtoupper($input['instrutor_nome'] ?? ''),
            'chainstrutor' => $input['instrutor_cat'] ?? '', 'numchainstrutor' => $input['instrutor_cha'] ?? '',
            'data' => $dataAula->format('d/m/Y'),
            'localdata' => ($c->cidade ?? 'Brasília') . ', ' . $dataAula->translatedFormat('d \d\e F \d\e Y'),
            'dia' => $dataAula->format('d'), 'mes' => $dataAula->format('m'), 'ano' => $dataAula->format('Y'),
        ];
    }
}