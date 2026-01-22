<?php
namespace App\Anexos;
use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;
use Filament\Forms\Components\Radio;

class Anexo5D implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 5D - Declaração de Extravio/Roubo CHA'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo5D-N211.pdf'); }
    public function getFormSchema(): array {
        return [Radio::make('motivo')->options(['extraviada'=>'Extraviada','roubada'=>'Roubada','furtada'=>'Furtada','danificada'=>'Danificada'])->required()];
    }

    public function getDados($record, array $input): array
    {
        Carbon::setLocale('pt_BR');
        $cliente = ($record instanceof \App\Models\Embarcacao) ? $record->cliente : $record;
        
        $dados = [
            'nome' => mb_strtoupper($cliente->nome ?? ''), 'rg' => $cliente->rg ?? '',
            'dtemissao' => $cliente->dt_emissao ? Carbon::parse($cliente->dt_emissao)->format('d/m/Y') : '',
            'cpf1' => $cliente->cpfcnpj ?? '', 'celular' => $cliente->celular ?? '',
            'logradouro' => mb_strtoupper($cliente->logradouro ?? ''),
            'complemento' => mb_strtoupper(($cliente->numero ?? '') . ' ' . ($cliente->complemento ?? '')),
            'bairro' => mb_strtoupper($cliente->bairro ?? ''), 'cidade' => mb_strtoupper(($cliente->cidade ?? '') . '/' . ($cliente->uf ?? '')),
            'cep' => $cliente->cep ?? '', 'cha' => $cliente->cha_numero ?? '',
            'dtemissaocha' => $cliente->cha_dtemissao ? Carbon::parse($cliente->cha_dtemissao)->format('d/m/Y') : '',
            'categoriacha' => mb_strtoupper($cliente->cha_categoria ?? ''),
            'localdata' => ($cliente->cidade ?? 'Brasília') . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];
        if (isset($input['motivo'])) { $dados[$input['motivo']] = 'Sim'; }
        return $dados;
    }
}