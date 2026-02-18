<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Embarcacao;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;

class Anexo2E implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2E - Requerimento Diversos'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2E-N211.pdf'); }

    public function getFormSchema(): array
    {
        return [
            CheckboxList::make('solicitacao')->label('Natureza do Requerimento')->columns(2)->required()->live()
                ->options([
                    'inscricao'=>'Inscrição de Embarcação', 'cancelamento'=>'Cancelamento de Inscrição',
                    'licencaconstrucao'=>'Licença de Construção', 'licencaalteracao'=>'Licença de Alteração',
                    'licencareclassificacao'=>'Licença de Reclassificação', 'transfpropriedade'=>'Transferência de Propriedade',
                    'transfjurisdicao'=>'Transferência de Jurisdição', 'transfpropjurisdicao'=>'Transferência de Propriedade e Jurisdição',
                    'mudancanome'=>'Mudança de Nome da Embarcação', 'renovacaotie'=>'Renovação de TIE/TIEM',
                    'segviatie'=>'2ª Via de TIE/TIEM', 'alteracaodadosembarcacao'=>'Alteração de dados cadastrais da embarcação',
                    'alteracaodadosproprietario'=>'Alteração de dados cadastrais do Proprietário', 'trocamotor'=>'Troca do Motor Propulsor',
                    'certidaoembarcacao'=>'Certidão Relativa à Situação da Embarcação', 'registroaverbacao'=>'Registro de Ônus e Averbações',
                    'cancaverbacao'=>'Cancelamento do Registro de Ônus', 'vistoriaarqueacao'=>'Vistoria de Arqueação',
                    'vistoriarearqueacao'=>'Vistoria de Rearqueação', 'vistoriaclassificacao'=>'Vistoria para alterar classificação',
                    'outrosservicos'=>'Outros Serviços',
                ]),
            Grid::make(3)->schema([
                TextInput::make('texto1')->label('1ª Opção de Nome'), TextInput::make('texto2')->label('2ª Opção de Nome'), TextInput::make('texto3')->label('3ª Opção de Nome'),
            ])->visible(fn (callable $get) => in_array('mudancanome', $get('solicitacao') ?? [])),
            Radio::make('renovacao_alteracao')->label('Houve alteração de característica?')
                ->options(['sim' => 'Sim', 'nao' => 'Não'])->inline()
                ->visible(fn (callable $get) => in_array('renovacaotie', $get('solicitacao') ?? [])),
            Radio::make('motivo_segvia')->label('Motivo da 2ª Via')
                ->options(['perda' => 'Perda', 'roubo' => 'Roubo', 'extravio' => 'Extravio', 'mauestado'=> 'Mau estado de conservação'])
                ->visible(fn (callable $get) => in_array('segviatie', $get('solicitacao') ?? [])),
            Textarea::make('justificativa')->label('Especificar Serviço')
                ->visible(fn (callable $get) => in_array('outrosservicos', $get('solicitacao') ?? []))->maxLength(140),
        ];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        $cliente = $embarcacao->cliente;

        $dados = [
            'nome' => $this->up($cliente->nome), 'logradouro' => $this->up($cliente->logradouro),
            'numero' => $cliente->numero ?? '', 'aptosala' => $this->up($cliente->complemento),
            'cidade' => $this->up($cliente->cidade), 'uf' => $this->up($cliente->uf),
            'cep' => $cliente->cep ?? '', 'rg' => $this->up($cliente->rg),
            'orgexpedidor' => $this->up($cliente->org_emissor), 'cpfcnpj' => $cliente->cpfcnpj ?? '',
            'telefone' => $this->up($cliente->telefone ?? $cliente->celular), 'cpfrequerente' => $cliente->cpfcnpj ?? '',
            'nomeembarcacao'=> $this->up($embarcacao->nome_embarcacao), 'numinscricao' => $this->up($embarcacao->num_inscricao),
            'comprimento' => $embarcacao->comp_total ? number_format($embarcacao->comp_total, 2, '.', '') . 'm' : '',
            'numcasco' => $this->up($embarcacao->num_casco), 'classificacao' => $this->up($embarcacao->tipo_atividade),
            'localdata' => $this->up($cliente->cidade ?? 'LOCAL') . ', ' . Carbon::now()->format('d/m/Y'),
        ];

        $selecionados = $input['solicitacao'] ?? [];
        foreach ($selecionados as $item) { $dados['check_' . $item] = 'Sim'; }

        if (in_array('mudancanome', $selecionados)) {
            $dados['nomeembarcacao1'] = $this->up($input['texto1']?? '');
            $dados['nomeembarcacao2'] = $this->up($input['texto2']?? '');
            $dados['nomeembarcacao3'] = $this->up($input['texto3']?? '');
        }
        if (in_array('outrosservicos', $selecionados)) {
            $dados['outrosservicos1'] = mb_substr($this->up($input['justificativa']?? ''), 0, 90);
        }
        if (in_array('renovacaotie', $selecionados)) {
            $sub = $input['renovacao_alteracao'] ?? null;
            if ($sub === 'sim') $dados['check_renovacaotie_sim'] = 'Sim';
            if ($sub === 'nao') $dados['check_renovacaotie_nao'] = 'Sim';
        }
        if (in_array('segviatie', $selecionados)) {
            $motivo = $input['motivo_segvia'] ?? null;
            if ($motivo) { $dados['check_segviatie_' . $motivo] = 'Sim'; }
        }

        return $dados;
    }

    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}