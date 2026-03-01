<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Embarcacao;
use Carbon\Carbon;
use Filament\Forms\Components\Select;

class Bsade implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2B - Atualização de Dados (BSADE)'; }
    public function getTemplatePath(): string { return storage_path('app/templates/Anexo2B-N211.pdf'); }

    public function getFormSchema(): array
    {
        return [
            Select::make('natureza')->label('Natureza do Requerimento')->options([
                'inscricao' => 'Inscrição de Embarcação', 'cancelamento' => 'Cancelamento',
                'transfpropriedade' => 'Transferência de Propriedade', 'transfjurisdicao' => 'Transferência de Jurisdição',
                'atualizacaodados' => 'Atualização de Dados', 'emissaocertidao' => 'Emissão de Certidão',
            ])->required(),
        ];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record; // Cast implícito
        $embarcacao->load('notaFiscal', 'motores'); // Carrega nota e motores
        $nf = $embarcacao->notaFiscal;
        $cliente = $embarcacao->cliente;

        $dados = [
            'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao),
            'inscricao'      => $this->up($embarcacao->num_inscricao),
            'tipo'           => $this->up($embarcacao->tipo_embarcacao),
            'atividade'      => $this->up($embarcacao->tipo_atividade),
            'tripulantes'    => $embarcacao->qtd_tripulantes,
            
            // CORREÇÃO 1: Formata apenas o ANO (Y)
            'anoconstrucao'  => $embarcacao->dt_construcao ? Carbon::parse($embarcacao->dt_construcao)->format('Y') : '',
            
            'passageiros'    => $embarcacao->lotacao,
            'numcasco'       => $this->up($embarcacao->num_casco),
            'matcasco'       => $this->up($embarcacao->mat_casco),
            'comprimento'    => $embarcacao->comp_total ? $embarcacao->comp_total . 'm' : '',
            'arqbruta'       => $embarcacao->arqueacao_bruta ? $embarcacao->arqueacao_bruta . 'm' : '',
            'arqliquida'     => $embarcacao->arqueacao_liquida ? $embarcacao->arqueacao_liquida . 'm' : '',
            'boca'           => $embarcacao->boca_moldada ? $embarcacao->boca_moldada . 'm' : '',
            'contorno'       => $embarcacao->contorno ? $embarcacao->contorno . 'm' : '',
            'pontal'         => $embarcacao->pontal_moldado ? $embarcacao->pontal_moldado . 'm' : '',
            
            'nomeproprietario' => $this->up($cliente->nome),
            'cpfcnpj'          => $cliente->cpfcnpj ?? '',
            'telefone'         => $this->up($cliente->telefone),
            'celular'          => $this->up($cliente->celular),
            'email'            => $this->up($cliente->email),
            'endereco'         => $this->up(($cliente->logradouro ?? '') . ', ' . ($cliente->numero ?? '')),
            'cidade'           => $this->up($cliente->cidade),
            'bairro'           => $this->up($cliente->bairro),
            'cep'              => $cliente->cep ?? '',
            'rg'               => $this->up($cliente->rg),
            'orgemissor'       => $this->up($cliente->org_emissor),
            'dtemissao'        => $cliente->dt_emissao ? Carbon::parse($cliente->dt_emissao)->format('d/m/Y') : '',

            'numnota'          => $this->up($nf->numero_nota ?? ''), 
            'dtvenda'          => ($nf && $nf->dt_venda) ? Carbon::parse($nf->dt_venda)->format('d/m/Y') : '',
            'vendedor'         => $this->up($nf->razao_social ?? ''), 
            'cpfcnpj_vendedor' => $nf->cnpj_vendedor ?? '',
            'local'            => $this->up($nf->local ?? ''), 
            
            'localdata' => $this->up($cliente->cidade ?? 'Brasília') . ', ' . Carbon::now()->format('d/m/Y'),
        ];

        $natureza = $input['natureza'] ?? '';
        $checkboxMap = [
            'inscricao'=>'check_inscricao', 'cancelamento'=>'check_cancelamento',
            'transfpropriedade'=>'check_transfproprietario', 'transfjurisdicao'=>'check_transfjurisdicao',
            'atualizacaodados'=>'check_atualizacaodados', 'emissaocertidao'=>'check_emissaocertidao',
        ];
        if (array_key_exists($natureza, $checkboxMap)) { $dados[$checkboxMap[$natureza]] = 'Sim'; }

        // CORREÇÃO 2: Lógica de Motores vs Potência Máxima
        if ($embarcacao->motores->isNotEmpty()) {
            // Se tem motores cadastrados, preenche eles individualmente
            foreach ($embarcacao->motores as $index => $motor) {
                $i = $index + 1;
                if ($i > 3) break;
                $dados["marcamotor{$i}"] = $this->up($motor->marca);
                $dados["potmotor{$i}"] = $motor->potencia;
                $dados["numseriemotor{$i}"] = $this->up($motor->num_serie);
            }
        } elseif (!empty($embarcacao->potencia_motor)) {
            // Se NÃO tem motores cadastrados, mas tem Potência Máxima definida na embarcação
            $dados['potmotor1'] = "Max. {$embarcacao->potencia_motor} HP";
        }

        return $dados;
    }

    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}