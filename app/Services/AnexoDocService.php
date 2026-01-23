<?php

namespace App\Services;

use App\Anexos\Contracts\AnexoInterface;
use PhpOffice\PhpWord\TemplateProcessor;

class AnexoDocService
{
    /**
     * Gera um documento Word (.docx) substituindo variáveis e converte para PDF.
     * O parâmetro $record pode ser uma Embarcacao ou um Cliente.
     */
    public function gerarAnexoDocx($record, AnexoInterface $anexo, array $input)
    {
        // 1. Valida e Carrega o Template DOCX
        $templatePath = $anexo->getTemplatePath();

        if (!file_exists($templatePath)) {
            throw new \Exception("Template DOCX não encontrado em: $templatePath");
        }

        // Verifica se é realmente um DOCX
        $ext = pathinfo($templatePath, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'docx') {
            throw new \Exception("Este serviço só processa arquivos .docx. O arquivo atual é .$ext");
        }

        $template = new TemplateProcessor($templatePath);

        // 2. Obtém os dados da classe do Anexo (Anexo3C, Procuracao, etc)
        // O método getDados já trata internamente se $record é Cliente ou Embarcação
        $dados = $anexo->getDados($record, $input);

        // 3. Substitui as variáveis no Word
        foreach ($dados as $key => $value) {
            // O PhpWord aceita UTF-8 nativo
            $template->setValue($key, $value);
        }

        // 4. Salva o DOCX Temporário
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir))
            mkdir($tempDir, 0777, true);

        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "anexo_" . uniqid() . ".docx";
        $template->saveAs($tempDocx);

        // 5. Converte para PDF usando LibreOffice (Compatível com Linux)
        $outputDir = storage_path("app/public/anexos_pdf");
        if (!file_exists($outputDir))
            mkdir($outputDir, 0755, true);

        // Nome do arquivo de saída esperado pelo LibreOffice
        // Nota: O LibreOffice salva com o mesmo nome do docx, apenas mudando a extensão
        $pdfFileName = pathinfo($tempDocx, PATHINFO_FILENAME) . '.pdf';
        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . $pdfFileName;

        try {
            // Comando para converter (funciona no Linux com LibreOffice instalado)
            // --headless: sem interface gráfica
            // --convert-to pdf: formato de saída
            // --outdir: pasta de destino
            $command = "export HOME=/tmp && libreoffice --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($tempDocx);

            // Executa o comando
            $output = null;
            $resultCode = null;
            exec($command, $output, $resultCode);

            if ($resultCode !== 0 || !file_exists($pdfPath)) {
                throw new \Exception("Falha na conversão LibreOffice. Código: $resultCode. Output: " . implode("\n", $output));
            }

        } catch (\Throwable $e) {
            throw new \Exception("Erro na conversão do DOCX para PDF: " . $e->getMessage());
        }

        // Limpeza opcional
        @unlink($tempDocx);

        return $pdfPath;
    }
}