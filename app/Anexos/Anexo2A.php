<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;

class Anexo2A implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2A - Requerimento Motoaquática'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2A-N212.pdf'); }

    public function getFormSchema(): array
    {
        return [
            CheckboxList::make('solicitacao')->label('Serviços Requeridos')->columns(2)->required()->live()
                ->options([
                    'inscricao'=>'Inscrição', 'cancelamento'=>'Cancelamento', 'transfpropriedade'=>'Transferência de Propriedade',
                    'transfjurisdicao'=>'Transferência de Jurisdição', 'transfpropjurisdicao'=>'Transf. Propriedade e Jurisdição',
                    'mudancanome'=>'Mudança de Nome', 'renovacaotie_sim'=>'Renovação TIE (com alteração)', 'renovacaotie_nao'=>'Renovação TIE (sem alteração)',
                    'segviatie_perda'=>'2ª Via TIE (Perda)', 'segviatie_roubo'=>'2ª Via TIE (Roubo)', 'segviatie_extravio'=>'2ª Via TIE (Extravio)',
                    'segviatie_mauestado'=>'2ª Via TIE (Mau Estado)', 'alteracaodadosembarcacao'=>'Alteração dados Embarcação',
                    'alteracaodadosproprietario'=>'Alteração dados Proprietário', 'outrosservicos'=>'Outros Serviços',
                ]),
            TextInput::make('nome1')->visible(fn(callable $get) => in_array('mudancanome', $get('solicitacao') ?? [])),
            TextInput::make('nome2')->visible(fn(callable $get) => in_array('mudancanome', $get('solicitacao') ?? [])),
            TextInput::make('nome3')->visible(fn(callable $get) => in_array('mudancanome', $get('solicitacao') ?? [])),
            TextInput::make('detalhe_servico')->visible(fn(callable $get) => in_array('outrosservicos', $get('solicitacao') ?? [])),
        ];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;
        $end = ($c->logradouro ?? '') . ', ' . ($c->complemento ?? '');

        $dados = [
            'nome' => $this->up($c->nome), 'logradouro' => $this->up($end), 'numero' => $c->numero ?? '',
            'cidade' => $this->up($c->cidade), 'uf' => $this->up($c->uf), 'rg' => $this->up($c->rg),
            'orgexpedidor' => $this->up($c->org_emissor), 'cep' => $c->cep ?? '', 'telefone' => $c->celular ?? '',
            'cpfcnpj' => $c->cpfcnpj ?? '', 'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao),
            'numinscricao' => $this->up($embarcacao->num_inscricao), 'comprimento' => $embarcacao->comp_total ? $embarcacao->comp_total.'m' : '',
            'numcasco' => $this->up($embarcacao->num_casco), 'localdata' => $this->up($c->cidade ?? 'Brasília') . ', ' . date('d/m/Y'),
        ];

        $selecionados = $input['solicitacao'] ?? [];
        $map = [
            'inscricao'=>'check_inscricao', 'cancelamento'=>'check_cancelamento', 'transfpropriedade'=>'check_transfpropriedade',
            'transfjurisdicao'=>'check_transfjurisdicao', 'transfpropjurisdicao'=>'check_transfpropjurisdicao', 'mudancanome'=>'check_mudancanome',
            'alteracaodadosembarcacao'=>'check_alteracaodadosembarcacao', 'alteracaodadosproprietario'=>'check_alteracaodadosproprietario',
            'outrosservicos'=>'check_outrosservicos'
        ];

        foreach ($selecionados as $opcao) {
            if (isset($map[$opcao])) $dados[$map[$opcao]] = 'Sim';
            if (str_contains($opcao, 'renovacaotie')) { $dados['check_renovacaotie'] = 'Sim'; $dados[$opcao] = 'Sim'; }
            if (str_contains($opcao, 'segviatie')) { $dados['check_segviatie'] = 'Sim'; $dados[$opcao] = 'Sim'; }
        }

        if (in_array('mudancanome', $selecionados)) {
            $dados['campotexto1'] = $this->up($input['nome1']); $dados['campotexto2'] = $this->up($input['nome2']); $dados['campotexto3'] = $this->up($input['nome3']);
        }
        if (in_array('outrosservicos', $selecionados)) {
            $txt = $this->up($input['detalhe_servico']);
            $dados['outrosservicos1'] = mb_substr($txt, 0, 70); $dados['outrosservicos2'] = mb_substr($txt, 70);
        }
        return $dados;
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}
