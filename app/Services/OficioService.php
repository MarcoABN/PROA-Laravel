<?php

namespace App\Services;

use App\Models\Oficio;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class OficioService
{
    public function gerarDocumento(Oficio $oficio)
    {
        $templatePath = storage_path('app/public/templates/oficioproa.docx');

        if (!file_exists($templatePath)) {
            throw new \Exception("Template 'oficioproa.docx' não encontrado.");
        }

        $template = new TemplateProcessor($templatePath);

        Carbon::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

        // --- DADOS DA ESCOLA ---
        $escola = $oficio->escola;
        $template->setValue('nomeescola', $this->up($escola->razao_social));

        // Formata Endereço Escola (Evita vírgulas soltas)
        $parts1 = array_filter([
            $escola->logradouro,
            $escola->numero ? "Nº {$escola->numero}" : "S/N",
            $escola->complemento,
            $escola->bairro
        ]);
        $template->setValue('enderecoescola1', $this->up(implode(', ', $parts1)));

        $parts2 = array_filter([
            $escola->cidade ? "{$escola->cidade}-{$escola->uf}" : null,
            $escola->cep ? "CEP: {$escola->cep}" : null
        ]);
        $template->setValue('enderecoescola2', $this->up(implode(', ', $parts2)));

        $template->setValue('telescola', $escola->telefone_responsavel ?? '');
        $template->setValue('emailescola', strtolower($escola->email_contato ?? ''));

        // --- DADOS DO OFÍCIO ---
        $template->setValue('numoficio', $oficio->numero_oficio);
        
        $dataExtenso = ($escola->cidade ?? 'GOIÂNIA') . ' - ' . ($escola->uf ?? 'GO') . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y');
        $template->setValue('localdata', $this->up($dataExtenso));

        // --- DADOS DA CAPITANIA ---
        $cap = $oficio->capitania;
        $template->setValue('destinatariocapitania', $this->up($cap->capitao_nome ?? 'COMANDANTE'));
        $template->setValue('funcaodestinatario', $this->up($cap->capitao_patente ?? ''));
        
        // --- CORREÇÃO: Substituição da variável ${capitania} que faltava ---
        $template->setValue('capitania', $this->up($cap->nome)); 

        // Endereço Capitania Formatado
        $endCapParts = array_filter([
            $cap->logradouro,
            $cap->numero,
            $cap->bairro,
            ($cap->cidade && $cap->uf) ? "{$cap->cidade}-{$cap->uf}" : null,
            $cap->cep ? "CEP: {$cap->cep}" : null
        ]);
        $template->setValue('enderecocapitania', $this->up(implode(', ', $endCapParts)));

        // --- CORPO DO TEXTO ---
        $template->setValue('assuntooficio', $this->up($oficio->assunto));

        $dataAulaFmt = $oficio->data_aula ? $oficio->data_aula->format('d/m/Y') : '__/__/____';
        $textoBase = "Participo que os alunos abaixo realizarão aulas práticas de Lancha e Moto-Aquática no dia {$dataAulaFmt}, na/o {$oficio->local_aula}, em {$oficio->cidade_aula}, conforme discriminado abaixo:";
        
        $template->setValue('texto', $textoBase);
        $template->setValue('periodoaula', $oficio->periodo_aula);

        // --- ALUNOS ---
        $itens = $oficio->itens()->with('cliente')->get();
        for ($i = 1; $i <= 6; $i++) {
            if (isset($itens[$i-1])) {
                $item = $itens[$i-1];
                $cliente = $item->cliente;
                
                $template->setValue("nomecliente{$i}", $this->up($cliente->nome));
                $template->setValue("cpfcliente{$i}", $this->formatarCpfCnpj($cliente->cpfcnpj));
                $tel = $cliente->celular ?? $cliente->telefone ?? '';
                $template->setValue("telcliente{$i}", $tel);
                $template->setValue("catcliente{$i}", $item->categoria);
            } else {
                $template->setValue("nomecliente{$i}", "");
                $template->setValue("cpfcliente{$i}", "");
                $template->setValue("telcliente{$i}", "");
                $template->setValue("catcliente{$i}", "");
            }
        }

        // --- INSTRUTOR ---
        $inst = $oficio->instrutor;
        $template->setValue('nomeinstrutor', $this->up($inst->nome ?? ''));
        $template->setValue('cpfinstrutor', $this->formatarCpfCnpj($inst->cpfcnpj ?? ''));
        $template->setValue('celinstrutor', $inst->celular ?? '');
        $template->setValue('chainstrutor', $inst->cha_numero ?? '');

        // --- SALVAR E CONVERTER ---
        $safeNum = str_replace('/', '-', $oficio->numero_oficio);
        $fileName = "Oficio_{$safeNum}";
        
        return $this->salvarEConverter($template, $fileName);
    }

    // --- MÉTODOS AUXILIARES (Mantidos) ---
    private function salvarEConverter(TemplateProcessor $template, $filenameBase)
    {
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);

        $outputDir = storage_path("app/public/documentos_gerados");
        if (!file_exists($outputDir)) mkdir($outputDir, 0755, true);

        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "{$filenameBase}.docx";
        $template->saveAs($tempDocx);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "{$filenameBase}.pdf";
        if (file_exists($pdfPath)) @unlink($pdfPath);

        // Ajuste automático Linux/Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $libreOfficePath = '"C:\Program Files\LibreOffice\program\soffice.exe"';
            $command = $libreOfficePath . ' --headless --convert-to pdf "' . $tempDocx . '" --outdir "' . $outputDir . '"';
        } else {
            $command = "export HOME=/tmp && libreoffice --headless --convert-to pdf " . escapeshellarg($tempDocx) . " --outdir " . escapeshellarg($outputDir);
        }

        $output = shell_exec($command . " 2>&1");

        if (!file_exists($pdfPath)) {
            @unlink($tempDocx); 
            throw new \Exception("Erro ao gerar PDF. Output: " . $output);
        }

        @unlink($tempDocx);
        return $pdfPath;
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