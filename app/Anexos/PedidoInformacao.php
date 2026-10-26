<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Cliente;
use App\Models\Embarcacao;
use Carbon\Carbon;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class PedidoInformacao implements AnexoInterface
{
    public function getTitulo(): string { return 'Pedido de Informação'; }
    public function getTemplatePath(): string { return storage_path('app/public/templates/PedidoInformacao.docx'); }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('protocolo')
                ->label('Número do Protocolo')
                ->placeholder('Ex: 2026/00123-4')
                ->required()
                ->maxLength(50)
                ->validationMessages(['required' => 'Informe o número do protocolo']),

            Textarea::make('solicitacao')
                ->label('Informação Solicitada')
                ->placeholder('Descreva a informação que está sendo solicitada...')
                ->helperText('Texto que aparece no documento abaixo de "2. INFORMAÇÃO SOLICITADA".')
                ->rows(4)
                ->required()
                // Acima disso o bloco de assinatura não cabe mais na primeira página
                ->maxLength(500)
                ->validationMessages(['required' => 'Informe a informação solicitada']),
        ];
    }

    public function getDados($record, array $input): array
    {
        Carbon::setLocale('pt_BR');

        // O botão é de cliente, mas mantemos o mesmo fallback dos demais anexos
        // para o caso de a origem ser uma Embarcação.
        if ($record instanceof Embarcacao) {
            $c = $record->cliente;
        } elseif ($record instanceof Cliente) {
            $c = $record;
        } else {
            $c = $record;
        }

        // Rua/Avenida com número e complemento, como nos outros anexos
        $logradouro = trim(($c->logradouro ?? '') . ', ' . ($c->numero ?? ''));
        if (!empty($c->complemento)) {
            $logradouro .= ' - ' . $c->complemento;
        }

        $dados = [
            'nome' => $this->up($c->nome),
            'cpf' => $this->formatarCpfCnpj($c->cpfcnpj ?? ''),
            'rg' => $this->up($c->rg),
            'org_emissor' => $this->up($c->org_emissor),
            'logradouro' => $this->up($logradouro),
            'bairro' => $this->up($c->bairro),
            'cidade' => $this->up($c->cidade),
            'uf' => $this->up($c->uf),
            'cep' => $c->cep ?? '',
            'telefone' => ($c->celular ?? '') ?: ($c->telefone ?? ''),
            'email' => $this->up($c->email),
            // Informados pelo usuário no momento da impressão
            'protocolo' => $input['protocolo'] ?? '',
            'solicitacao' => trim((string) ($input['solicitacao'] ?? '')),
            'data_extenso' => Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];

        // O escaping do PhpWord vem desabilitado por padrão (Settings::$outputEscapingEnabled
        // = false), então o valor é injetado cru no XML: um "&" ou "<" no cadastro ou no texto
        // digitado corromperia o documento. Escapamos tudo e, na solicitação, convertemos
        // quebras de linha em <w:br/> (depois do escape, para a tag não ser escapada).
        $dados = array_map(fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8'), $dados);
        $dados['solicitacao'] = preg_replace('/\R/u', '<w:br/>', $dados['solicitacao']);

        return $dados;
    }

    private function up($valor) { return mb_strtoupper((string) ($valor ?? ''), 'UTF-8'); }

    private function formatarCpfCnpj($valor)
    {
        $valor = preg_replace('/[^0-9]/', '', (string) $valor);
        if (strlen($valor) === 11) return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
        if (strlen($valor) === 14) return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
        return $valor;
    }
}
