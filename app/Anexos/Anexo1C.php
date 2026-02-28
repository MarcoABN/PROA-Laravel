<?php

namespace App\Anexos;

use App\Anexos\Contracts\AnexoInterface;
use Carbon\Carbon;
//esse é só teste

class Anexo1C implements AnexoInterface
{
    public function getTitulo(): string
    {
        return 'Anexo 1C - Declaração de Residência';
    }
    public function getTemplatePath(): string
    {
        return storage_path('app/public/templates/Anexo1C-N212.pdf');
    }
    public function getFormSchema(): array
    {
        return [];
    }

    public function getDados($record, array $input): array
    {
        $embarcacao = $record;
        Carbon::setLocale('pt_BR');
        $c = $embarcacao->cliente;

        $end = $this->up(sprintf('%s, %s, %s, %s, %s, CEP: %s', $c->logradouro, $c->numero, $c->complemento, $c->bairro, $c->cidade, $c->cep));

        // Lógica multibyte segura para quebrar o endereço sem cortar palavras
        $endereco1 = '';
        $endereco2 = '';

        if (mb_strlen($end) > 45) {
            // Busca o último espaço dentro dos primeiros 46 caracteres
            $corte = mb_strrpos(mb_substr($end, 0, 46), ' ');

            if ($corte !== false && $corte <= 45) {
                // Quebra exatamente no último espaço encontrado
                $endereco1 = mb_substr($end, 0, $corte);
                // Pega o restante do texto após o espaço
                $endereco2 = mb_substr($end, $corte + 1);
            } else {
                // Fallback de segurança: se houver uma palavra gigante sem espaços
                $endereco1 = mb_substr($end, 0, 45);
                $endereco2 = mb_substr($end, 45);
            }
        } else {
            $endereco1 = $end;
        }

        return [
            'nome' => $this->up($c->nome),
            'nacionalidade' => $this->up($c->nacionalidade),
            'naturalidade' => $this->up($c->naturalidade),
            'cpf' => $c->cpfcnpj ?? '',
            'telefone' => $c->telefone ?? '',
            'celular' => $c->celular ?? '',
            'email' => $this->up($c->email),
            'endereco1' => trim($endereco1),
            'endereco2' => trim($endereco2),
            'localdata' => $this->up($c->cidade ?? 'Brasília') . ', ' . Carbon::parse($embarcacao->data_emissao)->format('d/m/Y'),
        ];
    }
    private function up($valor)
    {
        return mb_strtoupper((string) ($valor ?? ''), 'UTF-8');
    }
}