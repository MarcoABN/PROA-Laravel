<?php
namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;

class Anexo3A implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 3A - Requerimento Motonauta'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo3A-N212.pdf'); }

    public function getFormSchema(): array
    {
        return [
            CheckboxList::make('servicos')
                ->label('Serviços Requeridos')
                ->options([
                    '1' => '1 - Emissão / Renovação / 2ª Via',
                    '2' => '2 - Emissão CHA-MTA-E (Temporária)',
                    '3' => '3 - Renovação com Agregação',
                    '4' => '4 - Credenciamento Escola/PF',
                    '5' => '5 - Credenciamento Locadora',
                    '6' => '6 - Renovação Credenciamento Escola/PF',
                    '7' => '7 - Renovação Credenciamento Locadora',
                    '8' => '8 - Descredenciamento Escola',
                    '9' => '9 - Descredenciamento Locadora',
                ])
                ->columns(2)->required(),
            Textarea::make('descricao')->label('Descrição do Pedido')->maxLength(200),
        ];
    }

    public function getDados($record, array $input): array
    {
        Carbon::setLocale('pt_BR');
        // Se vier Embarcacao, pega o cliente. Se vier Cliente, usa direto.
        $cliente = ($record instanceof \App\Models\Embarcacao) ? $record->cliente : $record;

        $dados = [
            'nome'          => mb_strtoupper($cliente->nome ?? ''),
            'cpf1'          => $cliente->cpfcnpj ?? '',
            'cpf2'          => $cliente->cpfcnpj ?? '',
            'rg'            => $cliente->rg ?? '',
            'orgexpedidor'  => $cliente->org_emissor ?? '',
            'logradouro'    => mb_strtoupper($cliente->logradouro ?? ''),
            'numero'        => $cliente->numero ?? '',
            'complemento'   => mb_strtoupper($cliente->complemento ?? ''),
            'bairro'        => mb_strtoupper($cliente->bairro ?? ''),
            'cidade'        => mb_strtoupper($cliente->cidade ?? ''),
            'uf'            => mb_strtoupper($cliente->uf ?? ''),
            'cep'           => $cliente->cep ?? '',
            'email'         => mb_strtoupper($cliente->email ?? ''),
            'local'         => mb_strtoupper($cliente->cidade ?? ''),
            'dia'           => date('d'), 'mes' => date('m'), 'ano' => date('Y'),
        ];

        $tel = $cliente->telefone ?? ''; $cel = $cliente->celular ?? '';
        $dados['dddtelefone'] = substr(preg_replace('/\D/', '', $tel), 0, 2);
        $dados['telefone']    = substr(preg_replace('/\D/', '', $tel), 2);
        $dados['dddcelular']  = substr(preg_replace('/\D/', '', $cel), 0, 2);
        $dados['celular']     = substr(preg_replace('/\D/', '', $cel), 2);

        $servicos = $input['servicos'] ?? [];
        foreach ($servicos as $servico) { $dados['servico' . $servico] = 'Sim'; }

        $descricao = mb_strtoupper($input['descricao'] ?? '');
        $linhas = explode("\n", wordwrap($descricao, 40, "\n", true));
        for ($i = 0; $i < 3; $i++) { $dados['descricao' . ($i + 1)] = $linhas[$i] ?? ''; }

        return $dados;
    }
}