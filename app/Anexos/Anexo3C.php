<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Capitania;
use Carbon\Carbon;
use Filament\Forms\Components\Select;

class Anexo3C implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 3C - Termo Responsabilidade'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo3C-N211.docx'); }

    public function getFormSchema(): array
    {
        return [Select::make('capitania_id')->label('Capitania')->options(fn() => Capitania::all()->pluck('nome', 'id'))
            ->default(fn() => Capitania::where('padrao', true)->first()?->id)->searchable()->preload()->required()];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;
        
        $rawNasc = $c->data_nasc ?? $c->data_nascimento ?? null;
        $dtNasc = $rawNasc ? Carbon::parse($rawNasc)->format('d/m/Y') : '';
        $dtRg = $c->dt_emissao ? Carbon::parse($c->dt_emissao)->format('d/m/Y') : '';
        $capitania = Capitania::find($input['capitania_id']);
        $ruaNumero = ($c->logradouro ?? '') . ', ' . ($c->numero ?? '') . (!empty($c->complemento) ? ' - ' . $c->complemento : '');

        return [
            'nome_cliente' => $this->up($c->nome), 'nacionalidade_cliente' => $this->up($c->nacionalidade),
            'dtnasc_cliente' => $dtNasc, 'rg_cliente' => $this->up($c->rg), 'orgexped_cliente' => $this->up($c->org_emissor),
            'dtexped_cliente' => $dtRg, 'cpf_cliente' => $this->formatarCpfCnpj($c->cpfcnpj ?? ''),
            'rua_numero_cliente' => $this->up($ruaNumero), 'bairro_cliente' => $this->up($c->bairro),
            'cep_cliente' => $c->cep ?? '', 'cidade_cliente' => $this->up(($c->cidade ?? '') . ' / ' . ($c->uf ?? '')),
            'telefone_cliente' => $c->celular ?? $c->telefone ?? '',
            'nome_embarcacao' => $this->up($embarcacao->nome_embarcacao), 'tipo_embarcacao' => $this->up($embarcacao->tipo_embarcacao),
            'capitania' => $this->up($capitania ? $capitania->nome : ''), 'num_inscricao' => $this->up($embarcacao->num_inscricao),
            'local_data_topo' => $this->up($embarcacao->cidade ?? 'Brasília') . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];
    }
    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
    private function formatarCpfCnpj($valor) {
        $valor = preg_replace('/[^0-9]/', '', (string)$valor);
        if (strlen($valor) === 11) return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
        if (strlen($valor) === 14) return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
        return $valor; 
    }
}