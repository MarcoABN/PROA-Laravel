<?php

namespace App\Services;

use App\Models\Oficio;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OficioService
{
    public function gerarDocumento(Oficio $oficio)
    {
        $templatePath = storage_path('app/public/templates/oficioproa.docx');
        if (!file_exists($templatePath)) {
            throw new \Exception("Template não encontrado em: {$templatePath}");
        }

        $template = new TemplateProcessor($templatePath);
        Carbon::setLocale('pt_BR');

        // --- 1. ESCOLA ---
        $escola = $oficio->escola;
        $template->setValue('nomeescola', $this->up($escola->razao_social));

        $parts1 = array_filter([$escola->logradouro, $escola->numero ? "Nº {$escola->numero}" : "S/N", $escola->complemento, $escola->bairro]);
        $template->setValue('enderecoescola1', $this->up(implode(', ', $parts1)));

        $parts2 = array_filter([$escola->cidade ? "{$escola->cidade}-{$escola->uf}" : null, $escola->cep ? "CEP: {$escola->cep}" : null]);
        $template->setValue('enderecoescola2', $this->up(implode(', ', $parts2)));

        $template->setValue('telescola', $escola->telefone_responsavel ?? '');
        $template->setValue('emailescola', strtolower($escola->email_contato ?? ''));

        // --- 2. CAPITANIA ---
        $cap = $oficio->capitania;
        $template->setValue('capitania', $this->up($cap->nome));
        $template->setValue('destinatariocapitania', $this->up($cap->capitao_nome ?? 'COMANDANTE'));
        $template->setValue('funcaodestinatario', $this->up($cap->capitao_patente ?? ''));

        $endCapParts = array_filter([$cap->logradouro, $cap->numero, $cap->bairro, ($cap->cidade && $cap->uf) ? "{$cap->cidade}-{$cap->uf}" : null, $cap->cep ? "CEP: {$cap->cep}" : null]);
        $template->setValue('enderecocapitania', $this->up(implode(', ', $endCapParts)));

        // --- 3. OFÍCIO E DATA ---
        $template->setValue('numoficio', $oficio->numero_oficio);
        $template->setValue('assuntooficio', $this->up($oficio->assunto));

        $cidadeFmt = Str::title(Str::lower($escola->cidade ?? 'Goiânia'));
        $uf = strtoupper($escola->uf ?? 'GO');
        $dia = Carbon::now()->day;
        $mes = Carbon::now()->translatedFormat('F');
        $ano = Carbon::now()->year;

        $template->setValue('localdata', "{$cidadeFmt} - {$uf}, {$dia} de {$mes} de {$ano}");

        $dataAulaFmt = $oficio->data_aula ? $oficio->data_aula->format('d/m/Y') : '__/__/____';
        $textoBase = "Participo que os alunos abaixo realizarão aulas práticas de Lancha e Moto-Aquática no dia {$dataAulaFmt}, na/o {$oficio->local_aula}, em {$oficio->cidade_aula}, conforme discriminado abaixo:";
        $template->setValue('texto', $textoBase);
        $template->setValue('periodoaula', $oficio->periodo_aula);

        // --- 4. ALUNOS ---
        $alunos = $oficio->itens()->with('cliente')->get();

        for ($i = 1; $i <= 6; $i++) {
            if (isset($alunos[$i - 1])) {
                $item = $alunos[$i - 1];
                $c = $item->cliente;

                $template->setValue("nomecliente{$i}", $this->up($c->nome));
                $template->setValue("cpfcliente{$i}", $this->formatarCpfCnpj($c->cpfcnpj));
                $template->setValue("telcliente{$i}", $c->celular ?? $c->telefone ?? '');
                $template->setValue("catcliente{$i}", $item->categoria);
                $template->setValue("periodoaula{$i}", $oficio->periodo_aula);
            } else {
                $template->setValue("nomecliente{$i}", "");
                $template->setValue("cpfcliente{$i}", "");
                $template->setValue("telcliente{$i}", "");
                $template->setValue("catcliente{$i}", "");
                $template->setValue("periodoaula{$i}", "");
            }
        }

        // --- 5. INSTRUTORES ---
        $instrutores = $oficio->instrutores_oficio()->with('prestador')->get();

        for ($j = 1; $j <= 4; $j++) {
            if (isset($instrutores[$j - 1])) {
                $p = $instrutores[$j - 1]->prestador;

                $template->setValue("nomeinstrutor{$j}", $this->up($p->nome));
                $template->setValue("cpfinstrutor{$j}", $this->formatarCpfCnpj($p->cpfcnpj));
                $template->setValue("celinstrutor{$j}", $p->celular ?? '');
                
                // Correção solicitada: Preenche "ARA e MTA" apenas se existir instrutor
                $template->setValue("aramta{$j}", "ARA e MTA");
                
                $template->setValue("chainstrutor{$j}", $p->cha_numero ?? '');
            } else {
                $template->setValue("nomeinstrutor{$j}", "");
                $template->setValue("cpfinstrutor{$j}", "");
                $template->setValue("celinstrutor{$j}", "");
                
                // Correção solicitada: Limpa o campo se não houver instrutor
                $template->setValue("aramta{$j}", "");
                
                $template->setValue("chainstrutor{$j}", "");
            }
        }

        // --- 6. ASSINATURA (RESPONSÁVEL LEGAL DA ESCOLA) ---
        // Certifique-se de ter criado o relacionamento 'responsavel' no model EscolaNautica
        $responsavel = $escola->responsavel;

        if ($responsavel) {
            $template->setValue('resplegal', $this->up($responsavel->nome));
            $template->setValue('cpfresplegal', $this->formatarCpfCnpj($responsavel->cpfcnpj));
        } else {
            $template->setValue('resplegal', 'RESPONSÁVEL NÃO CADASTRADO');
            $template->setValue('cpfresplegal', '');
        }

        // --- SALVAR ---
        $safeNum = str_replace('/', '-', $oficio->numero_oficio);
        $uniqueId = time();
        $fileName = "Oficio_{$safeNum}_{$uniqueId}";

        return $this->salvarEConverter($template, $fileName);
    }

    // --- Métodos Auxiliares ---
    private function salvarEConverter(TemplateProcessor $template, $filenameBase)
    {
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir))
            mkdir($tempDir, 0755, true);

        $outputDir = storage_path("app/public/documentos_gerados");
        if (!file_exists($outputDir))
            mkdir($outputDir, 0755, true);

        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "{$filenameBase}.docx";
        $template->saveAs($tempDocx);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "{$filenameBase}.pdf";
        if (file_exists($pdfPath))
            @unlink($pdfPath);

        // Verifica SO para comando correto do LibreOffice
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Caminho padrão Windows - ajuste se necessário
            $libreOfficePath = '"C:\Program Files\LibreOffice\program\soffice.exe"';
            $command = $libreOfficePath . ' --headless --convert-to pdf "' . $tempDocx . '" --outdir "' . $outputDir . '"';
        } else {
            // Padrão Linux
            $command = "export HOME=/tmp && libreoffice --headless --convert-to pdf " . escapeshellarg($tempDocx) . " --outdir " . escapeshellarg($outputDir);
        }

        shell_exec($command . " 2>&1");

        @unlink($tempDocx);

        if (!file_exists($pdfPath)) {
            throw new \Exception("Erro ao gerar PDF. Verifique se o LibreOffice está instalado.");
        }

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