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
        // 1. Carrega o template
        $templatePath = storage_path('app/public/templates/oficioproa.docx');

        if (!file_exists($templatePath)) {
            throw new \Exception("Template 'oficioproa.docx' não encontrado na pasta templates.");
        }

        $template = new TemplateProcessor($templatePath);

        // --- 2. CONFIGURAÇÃO LOCAL ---
        Carbon::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');

        // --- 3. DADOS DA ESCOLA ---
        $escola = $oficio->escola;
        $template->setValue('nomeescola', $this->up($escola->razao_social));

        // Endereço Escola
        $endEscola1 = ($escola->logradouro ?? '') . ', ' . ($escola->numero ?? 'S/N');
        if ($escola->complemento) $endEscola1 .= ' - ' . $escola->complemento;
        $endEscola1 .= ' - ' . ($escola->bairro ?? '');
        $template->setValue('enderecoescola1', $this->up($endEscola1));

        $endEscola2 = ($escola->cidade ?? '') . ' - ' . ($escola->uf ?? '') . ', CEP: ' . ($escola->cep ?? '');
        $template->setValue('enderecoescola2', $this->up($endEscola2));

        $template->setValue('telescola', $escola->telefone_responsavel ?? '');
        $template->setValue('emailescola', strtolower($escola->email_contato ?? ''));

        // --- 4. DADOS DO OFÍCIO E DATA ---
        $template->setValue('numoficio', $oficio->numero_oficio);
        
        $dataExtenso = ($escola->cidade ?? 'Brasília') . ' - ' . ($escola->uf ?? 'DF') . ', ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y');
        $template->setValue('localdata', $dataExtenso);

        // --- 5. DADOS DA CAPITANIA ---
        $cap = $oficio->capitania;
        $template->setValue('destinatariocapitania', $this->up($cap->capitao_nome ?? 'COMANDANTE'));
        $template->setValue('funcaodestinatario', $this->up($cap->capitao_patente ?? ''));

        $endCap = "{$cap->logradouro}, {$cap->numero}, {$cap->bairro}, {$cap->cidade}-{$cap->uf}, CEP: {$cap->cep}";
        $template->setValue('enderecocapitania', $this->up($endCap));

        // --- 6. CORPO DO TEXTO ---
        $template->setValue('assuntooficio', $this->up($oficio->assunto));

        $dataAulaFmt = $oficio->data_aula ? $oficio->data_aula->format('d/m/Y') : '__/__/____';
        $textoBase = "Participo que os alunos abaixo realizarão aulas práticas de Lancha e Moto-Aquática no dia {$dataAulaFmt}, na/o {$oficio->local_aula}, em {$oficio->cidade_aula}, conforme discriminado abaixo:";
        
        $template->setValue('texto', $textoBase);
        $template->setValue('periodoaula', $oficio->periodo_aula);

        // --- 7. ALUNOS (LOOP FIXO 1 a 6) ---
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

        // --- 8. INSTRUTOR ---
        $inst = $oficio->instrutor;
        $template->setValue('nomeinstrutor', $this->up($inst->nome ?? ''));
        $template->setValue('cpfinstrutor', $this->formatarCpfCnpj($inst->cpfcnpj ?? ''));
        $template->setValue('celinstrutor', $inst->celular ?? '');
        $template->setValue('chainstrutor', $inst->cha_numero ?? '');

        // --- 9. SALVAR E CONVERTER ---
        // Sanitiza o nome do arquivo para evitar problemas no sistema de arquivos
        $safeNum = str_replace('/', '-', $oficio->numero_oficio);
        $fileName = "Oficio_{$safeNum}";
        
        return $this->salvarEConverter($template, $fileName);
    }

    // --- MÉTODOS AUXILIARES ---

    private function salvarEConverter(TemplateProcessor $template, $filenameBase)
    {
        // 1. Cria diretórios se não existirem
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);

        $outputDir = storage_path("app/public/documentos_gerados");
        if (!file_exists($outputDir)) mkdir($outputDir, 0755, true);

        // 2. Salva o DOCX temporário
        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "{$filenameBase}.docx";
        $template->saveAs($tempDocx);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "{$filenameBase}.pdf";

        // Remove PDF antigo se existir (para evitar cache ou erro de permissão)
        if (file_exists($pdfPath)) @unlink($pdfPath);

        // 3. COMANDO DE CONVERSÃO LINUX
        // export HOME=/tmp é crucial para o www-data conseguir rodar o LibreOffice
        $command = "export HOME=/tmp && libreoffice --headless --convert-to pdf " . escapeshellarg($tempDocx) . " --outdir " . escapeshellarg($outputDir);

        // Executa o comando e captura a saída (stdout e stderr)
        $output = shell_exec($command . " 2>&1");

        // 4. Verifica se o PDF foi criado
        if (!file_exists($pdfPath)) {
            // Tenta limpar o docx antes de lançar o erro
            @unlink($tempDocx); 
            
            // Log do erro para debugging
            throw new \Exception("Erro ao gerar PDF no Linux. Verifique se o pacote 'libreoffice' está instalado. Output: " . $output);
        }

        // Limpeza do DOCX temporário
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