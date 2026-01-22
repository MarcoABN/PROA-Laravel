<?php
namespace App\Anexos;
use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;

class Anexo5H implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 5H - Requerimento CHA'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/Anexo5H-N211.pdf'); }
    public function getFormSchema(): array {
        return [
            CheckboxList::make('servicos')->columns(2)->required()->options([
                '1'=>'1 - Concessão por Equivalência', '2'=>'2 - Emissão / Renovação', '3'=>'3 - Renovação com Agregação',
                '4'=>'4 - Cadastramento Marina', '7'=>'7 - Credenciamento Escola', '8'=>'8 - Credenciamento Escoteiro',
                '9'=>'9 - Credenciamento Veleiro', '10a'=>'10A - Renovação Escola', '11'=>'11 - Descredenciamento', '12'=>'12 - Revisão de Prova'
            ]),
            Textarea::make('descricao')->maxLength(300),
        ];
    }

    public function getDados($record, array $input): array
    {
        $cliente = ($record instanceof \App\Models\Embarcacao) ? $record->cliente : $record;
        $dados = [
            'nome' => mb_strtoupper($cliente->nome ?? ''), 'cpf1' => $cliente->cpfcnpj ?? '', 'cpf2' => $cliente->cpfcnpj ?? '',
            'rg' => $cliente->rg ?? '', 'orgexpedidor' => $cliente->org_emissor ?? '',
            'logradouro' => mb_strtoupper($cliente->logradouro ?? ''), 'numero' => $cliente->numero ?? '',
            'complemento' => mb_strtoupper($cliente->complemento ?? ''), 'bairro' => mb_strtoupper($cliente->bairro ?? ''),
            'cidade' => mb_strtoupper($cliente->cidade ?? ''), 'uf' => mb_strtoupper($cliente->uf ?? ''),
            'cep' => $cliente->cep ?? '', 'email' => mb_strtoupper($cliente->email ?? ''),
            'local' => mb_strtoupper($cliente->cidade ?? ''), 'data' => date('d/m/Y'),
        ];
        
        $tel = $cliente->telefone ?? ''; $cel = $cliente->celular ?? '';
        $dados['dddtelefone'] = substr(preg_replace('/\D/', '', $tel), 0, 2); $dados['telefone'] = substr(preg_replace('/\D/', '', $tel), 2);
        $dados['dddcelular'] = substr(preg_replace('/\D/', '', $cel), 0, 2); $dados['celular'] = substr(preg_replace('/\D/', '', $cel), 2);

        $servicos = $input['servicos'] ?? [];
        foreach ($servicos as $servico) {
            $dados['servico' . $servico] = 'Sim';
            if (in_array($servico, ['10a', '10b', '10c'])) { $dados['servico10'] = 'Sim'; $dados['servico10_' . strtolower(substr($servico, 2))] = 'Sim'; }
        }

        $descricao = mb_strtoupper($input['descricao'] ?? '');
        $linhas = explode("\n", wordwrap($descricao, 60, "\n", true));
        for ($i = 0; $i < 7; $i++) { $dados['descricao' . ($i + 1)] = $linhas[$i] ?? ''; }

        return $dados;
    }
}