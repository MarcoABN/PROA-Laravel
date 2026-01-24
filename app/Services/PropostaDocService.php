<?php

namespace App\Services;

use App\Models\Proposta;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PropostaDocService
{
    public function gerarPdf(Proposta $proposta)
    {
        return $this->gerarDocumento($proposta, 'contrato');
    }

    public function gerarReciboPdf(Proposta $proposta)
    {
        return $this->gerarDocumento($proposta, 'recibo');
    }

    private function gerarDocumento(Proposta $proposta, string $tipo)
    {
        $templateName = $tipo === 'recibo' ? 'Recibo.docx' : 'CBS-PRS-04560-130126.docx';
        $templatePath = storage_path("app/public/templates/{$templateName}");

        if (!file_exists($templatePath)) {
            throw new \Exception("Template não encontrado: $templateName");
        }

        $template = new TemplateProcessor($templatePath);

        // --- 1. DADOS COMUNS ---
        $this->preencherDadosComuns($template, $proposta);

        // --- 2. SERVIÇOS ---
        // Contrato: até 11 itens / Recibo: até 4 itens
        $limiteServicos = $tipo === 'recibo' ? 4 : 11;
        $itens = $proposta->itens_servico ?? [];
        $totalBruto = 0;

        for ($i = 1; $i <= $limiteServicos; $i++) {
            $idx = $i - 1;
            if (isset($itens[$idx])) {
                $valor = (float) $itens[$idx]['valor'];
                $totalBruto += $valor;
                $template->setValue("nserv{$i}", $i);
                $template->setValue("descserv{$i}", $this->up($itens[$idx]['descricao']));
                $template->setValue("valorserv{$i}", 'R$ ' . number_format($valor, 2, ',', '.'));
            } else {
                $template->setValue("nserv{$i}", '');
                $template->setValue("descserv{$i}", '');
                $template->setValue("valorserv{$i}", '');
            }
        }

        // Soma itens ocultos (se houver) para o cálculo bater
        for ($k = $limiteServicos; $k < count($itens); $k++) {
            $totalBruto += (float) $itens[$k]['valor'];
        }

        // --- 3. TOTAIS, LABEL BRUTO E DESCONTO ---
        $desconto = (float) $proposta->valor_desconto;
        $totalLiquido = $totalBruto - $desconto;

        // Sempre exibe o Total Geral (Líquido)
        $template->setValue('valortotalserv', 'R$ ' . number_format($totalLiquido, 2, ',', '.'));

        // Lógica condicional para Bruto e Desconto
        if ($desconto > 0) {
            // Com Desconto: Exibe Bruto e o Desconto
            $template->setValue('label_total_bruto', 'Valor Total');
            $template->setValue('valorbrutoserv', 'R$ ' . number_format($totalBruto, 2, ',', '.'));

            $template->setValue('desc_label', 'DESCONTO');
            $template->setValue('descserv', 'R$ ' . number_format($desconto, 2, ',', '.'));
        } else {
            // Sem Desconto: Limpa o Bruto e o Desconto
            // Assim, só sobrará o "Total Geral" visível no recibo
            $template->setValue('label_total_bruto', '');
            $template->setValue('valorbrutoserv', '');

            $template->setValue('desc_label', '');
            $template->setValue('descserv', '');
        }

        // --- 4. PARCELAS ---
        $this->preencherParcelas($template, $proposta, 6);

        // --- 5. SALVAR E GERAR ---
        return $this->salvarArquivo($template, "{$tipo}_{$proposta->id}");
    }

    // --- MÉTODOS AUXILIARES ---

    private function preencherDadosComuns(TemplateProcessor $template, Proposta $proposta)
    {
        $escola = $proposta->escola;
        $cliente = $proposta->cliente;
        $embarcacao = $proposta->embarcacao;
        $responsavel = $escola->responsavel;

        // Logo
        if ($escola->logo_path && Storage::disk('public')->exists($escola->logo_path)) {
            $template->setImageValue('logo_empresa', [
                'path' => storage_path("app/public/{$escola->logo_path}"),
                'width' => 250,
                'height' => 125,
                'ratio' => true
            ]);
        } else {
            $template->setValue('logo_empresa', '');
        }

        // Cabeçalho
        $template->setValue('numero_proposta', $proposta->numero_formatado);
        Carbon::setLocale('pt_BR');
        $data = Carbon::parse($proposta->data_proposta);
        $cidade = ($escola->cidade ?? 'Brasília') . (!empty($escola->uf) ? '/' . $escola->uf : '');
        $template->setValue('local_data_topo', $cidade . ', ' . $data->translatedFormat('d \d\e F \d\e Y'));

        // Escola
        $template->setValue('proponente_topo', $this->up($escola->razao_social));
        $template->setValue('cnpj_proponente_topo', $this->formatarCpfCnpj($escola->cnpj));
        $template->setValue('cidade_proponente', $this->up($cidade));
        $template->setValue('site_proponente', rtrim(str_ireplace(['https://', 'http://'], '', $escola->site ?? ''), '/'));
        $template->setValue('email_proponente', $escola->email_contato ?? '');
        $template->setValue('contato_proponente', ($escola->telefone_responsavel ?? '') . ($escola->telefone_secundario ? ' / ' . $escola->telefone_secundario : ''));
        $template->setValue('contato_proponente2', '');

        $respTexto = $responsavel ? $responsavel->nome . ' - CPF ' . $this->formatarCpfCnpj($responsavel->cpfcnpj) : '';
        $template->setValue('responsavel_escolanautica', $this->up($respTexto));

        // Bancos
        foreach (['banco', 'agencia', 'conta_corrente', 'chave_pix'] as $campo) {
            $keyWord = ($campo == 'agencia') ? 'ag' : ($campo == 'conta_corrente' ? 'cc' : ($campo == 'chave_pix' ? 'pix' : $campo));
            $template->setValue("{$keyWord}_proponente", $this->up($escola->$campo));
        }

        // Cliente
        $template->setValue('cliente_aceitante', $this->up($cliente->nome));
        $template->setValue('cnpj_aceitante', $this->formatarCpfCnpj($cliente->cpfcnpj));
        $endereco = ($cliente->logradouro ?? '') . ', ' . ($cliente->numero ?? '');
        $template->setValue('endereco_aceitante', $this->up($endereco));

        // Embarcação (Trata Nulo)
        if ($embarcacao) {
            $template->setValue('nome_embarcacao', $this->up($embarcacao->nome_embarcacao));
            $template->setValue('tipo_embarcacao', $this->up($embarcacao->tipo_embarcacao));
            $template->setValue('comp_embarcacao', $embarcacao->comp_total ? $embarcacao->comp_total . 'm' : 'N/A');
        } else {
            $template->setValue('nome_embarcacao', '---');
            $template->setValue('tipo_embarcacao', '---');
            $template->setValue('comp_embarcacao', '---');
        }
        $template->setValue('ab', '0');
    }

    private function preencherParcelas(TemplateProcessor $template, Proposta $proposta, int $max)
    {
        $parcelas = $proposta->parcelas ?? [];
        $qtd = count($parcelas);
        $extensoMap = [0 => 'À VISTA', 1 => 'UMA PARCELA', 2 => 'DUAS PARCELAS', 3 => 'TRÊS PARCELAS', 4 => 'QUATRO PARCELAS', 5 => 'CINCO PARCELAS', 6 => 'SEIS PARCELAS'];
        $template->setValue('qtd_parcela_extenso', $this->up($extensoMap[$qtd] ?? "$qtd PARCELAS"));

        for ($i = 1; $i <= $max; $i++) {
            $idx = $i - 1;
            if (isset($parcelas[$idx])) {
                $template->setValue("parc{$i}", "{$i} / {$qtd}");
                $template->setValue("descparc{$i}", $this->up($parcelas[$idx]['descricao']));
                $template->setValue("valorparc{$i}", 'R$ ' . number_format((float) $parcelas[$idx]['valor'], 2, ',', '.'));
            } else {
                $template->setValue("parc{$i}", '');
                $template->setValue("descparc{$i}", '');
                $template->setValue("valorparc{$i}", '');
            }
        }
    }

    private function salvarEConverter(TemplateProcessor $template, $filenameBase)
    {
        // 1. Cria diretório temporário para arquivos
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir))
            mkdir($tempDir, 0755, true);

        // 2. Salva o DOCX modificado
        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "{$filenameBase}.docx";
        $template->saveAs($tempDocx);

        // 3. Define diretório de saída
        $outputDir = storage_path("app/public/documentos_gerados");
        if (!file_exists($outputDir))
            mkdir($outputDir, 0755, true);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "{$filenameBase}.pdf";
        if (file_exists($pdfPath))
            @unlink($pdfPath);

        // 4. CONVERSÃO COM CORREÇÃO DE HOME (Linux)
        // O "export HOME=/tmp" diz ao LibreOffice para usar a pasta temporária
        // para gravar seus arquivos de config, evitando o erro de permissão no /var/www
        $command = "export HOME=/tmp && libreoffice --headless --convert-to pdf " . escapeshellarg($tempDocx) . " --outdir " . escapeshellarg($outputDir);

        $output = shell_exec($command . " 2>&1");

        // 5. Verificação
        if (!file_exists($pdfPath)) {
            // Remove o DOCX para não acumular lixo
            @unlink($tempDocx);
            throw new \Exception("Erro ao gerar PDF. Log do sistema: " . $output);
        }

        // Limpeza do arquivo temporário
        @unlink($tempDocx);

        return $pdfPath;
    }

    private function up($v)
    {
        return mb_strtoupper((string) ($v ?? ''), 'UTF-8');
    }
    private function formatarCpfCnpj($v)
    {
        $v = preg_replace('/[^0-9]/', '', (string) $v);
        if (strlen($v) == 11)
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $v);
        if (strlen($v) == 14)
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $v);
        return $v;
    }
}