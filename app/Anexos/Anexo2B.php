<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;
use Filament\Forms\Components\Select;

class Anexo2B implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2B - Boletim BDMOTO'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo2B-N212.pdf'); }

    public function getFormSchema(): array
    {
        return [Select::make('natureza')->options([
            'Inscrição'=>'Inscrição', 'Cancelamento'=>'Cancelamento', 'Transf. Propriedade'=>'Transf. Propriedade',
            'Transf. Jurisdição'=>'Transf. Jurisdição', 'Atualização de Dados'=>'Atualização de Dados'
        ])->required()];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;
        $nf = $embarcacao->notaFiscal;
        
        $dados = [
            'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao), 'inscricao' => $this->up($embarcacao->num_inscricao),
            'anoconstrucao' => $embarcacao->dt_construcao ? Carbon::parse($embarcacao->dt_construcao)->format('d/m/Y') : '',
            'passageiros' => $embarcacao->lotacao ?? '', 'numcasco' => $this->up($embarcacao->num_casco),
            'comprimento' => $embarcacao->comp_total ? $embarcacao->comp_total.'m' : '',
            'nomeproprietario' => $this->up($c->nome),
            'endereco' => $this->up(($c->logradouro ?? '') . ', ' . ($c->numero ?? '') . ' - ' . ($c->complemento ?? '')),
            'cidade' => $this->up($c->cidade), 'bairro' => $this->up($c->bairro), 'cep' => $c->cep ?? '',
            'rg' => $this->up($c->rg), 'orgemissor' => $this->up($c->org_emissor),
            'dtemissao' => $c->dt_emissao ? Carbon::parse($c->dt_emissao)->format('d/m/Y') : '',
            'cpfcnpj' => $c->cpfcnpj ?? '', 'telefone' => $c->telefone ?? '', 'celular' => $c->celular ?? '',
            'email' => $this->up($c->email), 'localdata' => $this->up($c->cidade ?? 'Brasília') . ', ' . date('d/m/Y'),
        ];

        $map = ['Inscrição'=>'check_inscricao', 'Cancelamento'=>'check_cancelamento', 'Transf. Propriedade'=>'check_transfproprietario',
                'Transf. Jurisdição'=>'check_transfjurisdicao', 'Atualização de Dados'=>'check_atualizacaodados'];
        if (isset($map[$input['natureza']])) $dados[$map[$input['natureza']]] = 'Sim';

        foreach ($embarcacao->motores as $i => $motor) {
            $idx = $i + 1; if ($idx > 3) break;
            $dados["marcamotor{$idx}"] = $this->up($motor->marca); $dados["potmotor{$idx}"] = $motor->potencia; $dados["numseriemotor{$idx}"] = $this->up($motor->num_serie);
        }
        if ($nf) {
            $dados['numnota'] = $nf->numero_nota; $dados['dtvenda'] = $nf->dt_venda ? Carbon::parse($nf->dt_venda)->format('d/m/Y') : '';
            $dados['vendedor'] = $this->up($nf->razao_social); $dados['cpfcnpj_vendedor'] = $nf->cnpj_vendedor; $dados['local'] = $this->up($c->cidade); 
        }
        return $dados;
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}