<?php

namespace App\Services;

use App\Anexos\Contracts\AnexoInterface;
use mikehaertl\pdftk\Pdf;
use Illuminate\Support\Facades\Log;

class AnexoService
{
    /**
     * Gera o PDF preenchendo os campos via PDFtk.
     * O parâmetro $record pode ser uma Embarcacao ou um Cliente.
     */
    public function gerarPdf(AnexoInterface $anexo, $record, array $input)
    {
        $templatePath = $anexo->getTemplatePath();

        if (!file_exists($templatePath)) {
            throw new \Exception("Template não encontrado: $templatePath");
        }

        // Pega os dados processados pela classe específica do anexo.
        // O método getDados do anexo já sabe lidar se $record é Cliente ou Embarcação.
        $dados = $anexo->getDados($record, $input);

        // Geração Padrão com PDFtk
        $pdf = new Pdf($templatePath);
        
        // Define um caminho temporário único
        $tempPath = storage_path('app/public/temp_' . uniqid() . '.pdf');

        // Preenche o formulário e achata (flatten) para não ser mais editável
        $result = $pdf->fillForm($dados)
            ->flatten()
            ->saveAs($tempPath);

        if ($result === false) {
            Log::error("Erro PDFtk: " . $pdf->getError());
            throw new \Exception("Erro ao gerar PDF: " . $pdf->getError());
        }

        // Lê o conteúdo do arquivo gerado
        $content = file_get_contents($tempPath);
        
        // Remove o arquivo temporário para não lotar o servidor
        @unlink($tempPath);

        return $content;
    }
}