<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Cliente;
use App\Models\Embarcacao;
use Carbon\Carbon;
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

        return [
            'nome' => $this->up($c->nome),
            'cpf' => $this->formatarCpfCnpj($c->cpfcnpj ?? ''),
            'rg' => $this->up($c->rg),
            'org_emissor' => $this->up($c->org_emissor),
            'logradouro' => $this->up($logradouro),
            'bairro' => $this->up($c->bairro),
            'cidade' => $this->up(trim(($c->cidade ?? '') . ' / ' . ($c->uf ?? ''), ' /')),
            'cep' => $c->cep ?? '',
            'telefone' => $c->celular ?? $c->telefone ?? '',
            'email' => $this->up($c->email),
            // Informado pelo usuário no momento da impressão
            'protocolo' => $input['protocolo'] ?? '',
            'data_extenso' => Carbon::now()->translatedFormat('d \d\e F \d\e Y'),
        ];
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
