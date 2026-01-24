<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Embarcacao;
use App\Models\Prestador; // Usando o Model correspondente à tabela 'prestadores'
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ProcuracaoService
{
    public function gerarProcuracao02(Cliente $cliente, ?int $embarcacaoId = null)
    {
        // 1. Carrega o template
        $templatePath = storage_path('app/public/templates/procuracao02.docx');

        if (!file_exists($templatePath)) {
            throw new \Exception("Template 'procuracao02.docx' não encontrado na pasta templates.");
        }

        $template = new TemplateProcessor($templatePath);
        $embarcacao = $embarcacaoId ? Embarcacao::find($embarcacaoId) : null;

        // --- 2. DADOS DO CLIENTE ---
        $template->setValue('nomecliente', $this->up($cliente->nome));

        // Concatena endereço do cliente
        $endCliente = ($cliente->logradouro ?? '') . ', ' . ($cliente->numero ?? '');
        $template->setValue('enderecocliente', $this->up($endCliente));

        $template->setValue('cep', $cliente->cep);
        $template->setValue('cidade', $this->up($cliente->cidade)); // Cidade no endereço
        $template->setValue('bairro', $this->up($cliente->bairro));
        $template->setValue('rg', $cliente->rg ?? '');
        $template->setValue('orgexpedidor', $this->up($cliente->org_emissor ?? ''));
        $template->setValue('cpfcliente', $this->formatarCpfCnpj($cliente->cpfcnpj));
        $template->setValue('email', strtolower($cliente->email));
        $template->setValue('celular', $cliente->celular);

        // --- 3. DADOS DA EMBARCAÇÃO (Condicional) ---
        if ($embarcacao) {
            $template->setValue('label_embarcacao', 'NOME DA EMBARCAÇÃO:');
            $template->setValue('nome_embarcacao', $this->up($embarcacao->nome_embarcacao));
        } else {
            // Limpa os campos se não houver embarcação
            $template->setValue('label_embarcacao', '');
            $template->setValue('nome_embarcacao', '');
        }

        // --- 4. LOCAL E DATA ---
        Carbon::setLocale('pt_BR');

        // Define a cidade base: Se optou por incluir embarcação, tenta usar a cidade dela
        $cidadeBase = ($embarcacao && !empty($embarcacao->cidade))
            ? $embarcacao->cidade
            : $cliente->cidade;

        // Fallback para Goiânia se tudo for nulo, ou ajuste conforme regra de negócio
        $cidadeBase = $cidadeBase ?? 'Goiânia';

        $dataExtenso = $this->up($cidadeBase) . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y');
        $template->setValue('local_data', $dataExtenso);

        // --- 5. PROCURADORES (Lógica Dinâmica) ---
        $this->preencherProcuradores($template);

        // --- 6. SALVAR E CONVERTER ---
        $fileName = "procuracao_{$cliente->id}_" . time();
        return $this->salvarEConverter($template, $fileName);
    }

    private function preencherProcuradores(TemplateProcessor $template)
    {
        // Busca apenas quem é procurador na tabela 'prestadores'
        $procuradores = Prestador::where('is_procurador', true)->get();

        $textosCompletos = [];
        $textosReduzidos = [];

        foreach ($procuradores as $proc) {
            $cpf = $this->formatarCpfCnpj($proc->cpfcnpj);

            // Monta o RG com Órgão Emissor se houver
            $rgTexto = $proc->rg;
            if (!empty($proc->org_emissor)) {
                $rgTexto .= ' ' . $proc->org_emissor;
            }

            // Verifica o tipo (ENUM: COMPLETO ou REDUZIDO)
            if ($proc->tipo_procuracao === 'COMPLETO') {
                // Monta endereço completo do procurador
                $enderecoProc = $proc->logradouro;
                if ($proc->numero)
                    $enderecoProc .= ", nº {$proc->numero}";
                if ($proc->complemento)
                    $enderecoProc .= " ({$proc->complemento})";
                if ($proc->bairro)
                    $enderecoProc .= ", Bairro {$proc->bairro}";
                if ($proc->cidade)
                    $enderecoProc .= ", cidade de {$proc->cidade}";
                if ($proc->uf)
                    $enderecoProc .= "-{$proc->uf}";

                $partes = [
                    "Sr. " . $this->up($proc->nome),
                    $proc->nacionalidade ?? 'brasileiro',
                    $proc->estado_civil,
                    $proc->profissao,
                    "portador da Carteira de Identidade nº {$rgTexto} e CPF {$cpf}",
                    "residente e domiciliado na " . ($enderecoProc ?? 'endereço não informado')
                ];

                // Filtra campos vazios e une com vírgula
                $textosCompletos[] = implode(', ', array_filter($partes));
            } else {
                // Tipo REDUZIDO
                $partes = [
                    "Sr. " . $this->up($proc->nome),
                    "portador da Carteira de Identidade nº {$rgTexto} e CPF {$cpf}"
                ];
                $textosReduzidos[] = implode(', ', array_filter($partes));
            }
        }

        // Insere no template com gramática correta (A, B e C)
        $template->setValue('procuradores_completo', $this->listarGramaticalmente($textosCompletos));
        $template->setValue('procuradores_reduzido', $this->listarGramaticalmente($textosReduzidos));
    }

    // Une array com vírgulas e "e" no final
    private function listarGramaticalmente(array $lista)
    {
        if (empty($lista))
            return '';
        if (count($lista) === 1)
            return $lista[0];

        $ultimo = array_pop($lista);
        return implode('; ', $lista) . ' e ' . $ultimo;
    }

    private function salvarEConverter(TemplateProcessor $template, $filenameBase)
    {
        // 1. Cria diretório temporário
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir))
            mkdir($tempDir, 0755, true);

        // 2. Salva o DOCX modificado temporariamente
        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "{$filenameBase}.docx";
        $template->saveAs($tempDocx);

        // 3. Define diretório de saída
        $outputDir = storage_path("app/public/documentos_gerados");
        if (!file_exists($outputDir))
            mkdir($outputDir, 0755, true);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "{$filenameBase}.pdf";

        // Remove arquivo antigo se existir
        if (file_exists($pdfPath))
            @unlink($pdfPath);

        // 4. CONVERSÃO LINUX (Usando LibreOffice Headless)
        // O comando exporta o PDF para o diretório de saída ($outputDir)
        // "2>&1" captura erros para podermos debugar se falhar
        $command = "libreoffice --headless --convert-to pdf " . escapeshellarg($tempDocx) . " --outdir " . escapeshellarg($outputDir);

        $output = shell_exec($command . " 2>&1");

        // 5. Verificação e Limpeza
        if (!file_exists($pdfPath)) {
            // Se o PDF não foi criado, lança erro com o log do comando
            throw new \Exception("Erro ao gerar PDF. Verifique se o LibreOffice está instalado. Log: " . $output);
        }

        // Remove o DOCX temporário para não encher o servidor
        @unlink($tempDocx);

        return $pdfPath;
    }

    private function up($valor)
    {
        return mb_strtoupper((string) ($valor ?? ''), 'UTF-8');
    }

    private function formatarCpfCnpj($valor)
    {
        $valor = preg_replace('/[^0-9]/', '', (string) $valor);
        if (strlen($valor) === 11)
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
        if (strlen($valor) === 14)
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
        return $valor;
    }
}