<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use App\Models\Cliente;
use App\Models\Embarcacao;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;

class AutorizacaoTransferenciaMotoaquatica implements AnexoInterface
{
    public function getTitulo(): string { return 'Anexo 2D - Autorização Transf. (212)'; }
    public function getTemplatePath(): string { return storage_path('app/templates/Anexo2D-N212.pdf'); }

    public function getFormSchema(): array
    {
        return [
            Section::make('Dados do Vendedor')->schema([
                Select::make('buscar_vendedor')->searchable()->options(Cliente::limit(50)->pluck('nome', 'id'))
                    ->live()->afterStateUpdated(function ($state, Set $set) {
                        if ($cliente = Cliente::find($state)) {
                            $set('vendedor_nome', $cliente->nome); 
                            $set('vendedor_cpf', $cliente->cpfcnpj);
                            $set('vendedor_telefone', $cliente->celular ?? $cliente->telefone); 
                            $set('vendedor_email', $cliente->email);
                            $set('vendedor_logradouro', $cliente->logradouro); 
                            $set('vendedor_bairro', $cliente->bairro);
                        }
                    }),
                TextInput::make('vendedor_nome')->required(),
                Grid::make(3)->schema([
                    TextInput::make('vendedor_cpf')->required(), 
                    TextInput::make('vendedor_telefone'), 
                    TextInput::make('vendedor_email')
                ]),
                Grid::make(2)->schema([
                    TextInput::make('vendedor_logradouro'), 
                    TextInput::make('vendedor_bairro')
                ]),
            ]),
            Section::make('Dados da Venda')->schema([
                TextInput::make('valor')->label('Valor da venda')->prefix('R$')->placeholder('Ex.: 150.000,00')
                    ->default(fn ($livewire) => ($v = Embarcacao::find($livewire->data['embarcacao_id'] ?? null)?->valor)
                        ? number_format($v, 2, ',', '.') : null),
            ]),
        ];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        
        $dados = [
            'nomeembarcacao' => $this->up($embarcacao->nome_embarcacao), 
            'inscricao' => $this->up($embarcacao->num_inscricao),
            'valorcomprador' => $this->formatarValor($input['valor'] ?? null, $embarcacao->valor),
            
            // Dados do Vendedor
            'nomeproprietario' => $this->up($input['vendedor_nome'] ?? ''), 
            'cpfcnpjproprietario' => $input['vendedor_cpf'] ?? '',
            'telefoneproprietario' => $input['vendedor_telefone'] ?? '', 
            'emailproprietario' => $this->up($input['vendedor_email'] ?? ''),
            'rua' => $this->up($input['vendedor_logradouro'] ?? ''), 
            'bairro' => $this->up($input['vendedor_bairro'] ?? ''),
            
            // Dados do Comprador
            'nomecomprador' => $this->up($embarcacao->cliente->nome), 
            'cpfcnpjcomprador' => $embarcacao->cliente->cpfcnpj ?? '',
            'telefone' => $embarcacao->cliente->celular ?? '', 
            'emailcomprador' => $this->up($embarcacao->cliente->email),
            'logradourocomprador' => $this->up($embarcacao->cliente->logradouro), 
            'numerocomprador' => $embarcacao->cliente->numero ?? '',
            'complementocomprador' => $this->up($embarcacao->cliente->complemento), 
            'bairrocomprador' => $this->up($embarcacao->cliente->bairro),
            'cidadecomprador' => $this->up($embarcacao->cliente->cidade), 
            'cepcomprador' => $embarcacao->cliente->cep ?? '',
            
            'localdata' => $this->up($this->cidadeDoLocal($embarcacao)) . ', ' . date('d/m/Y'),
        ];

        // Lógica dos motores
        $motores = $embarcacao->motores;
        for ($i = 1; $i <= 4; $i++) {
            $dados["motor{$i}_sim"] = ($i <= $motores->count()) ? 'Sim' : 'Off';
            $dados["motor{$i}_nao"] = ($i > $motores->count()) ? 'Sim' : 'Off';
            if ($i <= $motores->count() && isset($motores[$i-1])) {
                $dados["marcamotor{$i}"] = $this->up($motores[$i-1]->marca);
                $dados["potenciamotor{$i}"] = $motores[$i-1]->potencia;
                $dados["numseriemotor{$i}"] = $this->up($motores[$i-1]->num_serie);
            }
        }
        return $dados;
    }
    
    /**
     * Cidade do "Local e Data".
     *
     * Antes era `$embarcacao->cidade ?? 'Brasília'`. Como 11% das embarcações estão
     * sem cidade, o documento saía com BRASÍLIA — um literal sem relação alguma com
     * o cadastro. Agora cai para a cidade do cliente (o comprador), que existe em
     * todos esses casos. Usa trim() porque `??` não pega string vazia.
     */
    private function cidadeDoLocal($embarcacao): string
    {
        $candidatas = [
            $embarcacao->cidade ?? null,
            $embarcacao->cliente->cidade ?? null,
        ];

        foreach ($candidatas as $cidade) {
            if (trim((string) $cidade) !== '') {
                return trim((string) $cidade);
            }
        }

        return '';
    }

    // Aceita "150.000,00", "150000" ou "150000.50"; texto não numérico vai como digitado
    private function formatarValor($digitado, $valorEmbarcacao): string
    {
        $digitado = trim((string) ($digitado ?? ''));
        if ($digitado === '') {
            return $valorEmbarcacao ? 'R$ ' . number_format($valorEmbarcacao, 2, ',', '.') : '';
        }
        $limpo = preg_replace('/[^\d,.]/', '', $digitado);
        $numero = str_contains($limpo, ',') || preg_match('/^\d{1,3}(\.\d{3})+$/', $limpo)
            ? str_replace(['.', ','], ['', '.'], $limpo) : $limpo;
        if (is_numeric($numero) && substr_count($numero, '.') <= 1) {
            return 'R$ ' . number_format((float) $numero, 2, ',', '.');
        }
        return $digitado;
    }

    private function up($valor) { return mb_strtoupper((string)($valor ?? ''), 'UTF-8'); }
}