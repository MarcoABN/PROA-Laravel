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
            throw new \Exception("Template não encontrado.");
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

        // --- 3. OFÍCIO E DATA (Formatação Tradicional) ---
        $template->setValue('numoficio', $oficio->numero_oficio);
        $template->setValue('assuntooficio', $this->up($oficio->assunto));

        // Formatação da Data: Goiânia - GO, 14 de fevereiro de 2026
        // Str::title converte "goiânia" para "Goiânia" e "fevereiro" para "Fevereiro"
        // Mas a regra pede mês minúsculo em pt-BR. O Carbon já traz minúsculo.
        // Apenas a cidade precisa de Title Case.
        $cidadeFmt = Str::title(Str::lower($escola->cidade ?? 'Goiânia'));
        $uf = strtoupper($escola->uf ?? 'GO');
        $dia = Carbon::now()->day;
        $mes = Carbon::now()->translatedFormat('F'); // janeiro, fevereiro...
        $ano = Carbon::now()->year;
        
        // Resultado: Goiânia - GO, 14 de fevereiro de 2026
        $template->setValue('localdata', "{$cidadeFmt} - {$uf}, {$dia} de {$mes} de {$ano}");

        // Texto do corpo
        $dataAulaFmt = $oficio->data_aula ? $oficio->data_aula->format('d/m/Y') : '__/__/____';
        $textoBase = "Participo que os alunos abaixo realizarão aulas práticas de Lancha e Moto-Aquática no dia {$dataAulaFmt}, na/o {$oficio->local_aula}, em {$oficio->cidade_aula}, conforme discriminado abaixo:";
        $template->setValue('texto', $textoBase);
        $template->setValue('periodoaula', $oficio->periodo_aula); // Caso exista a variável solta no texto

        // --- 4. ALUNOS (Lógica corrigida: Período preenchido linha a linha) ---
        $alunos = $oficio->itens()->with('cliente')->get();
        
        for ($i = 1; $i <= 6; $i++) {
            if (isset($alunos[$i-1])) {
                $item = $alunos[$i-1];
                $c = $item->cliente;
                
                $template->setValue("nomecliente{$i}", $this->up($c->nome));
                $template->setValue("cpfcliente{$i}", $this->formatarCpfCnpj($c->cpfcnpj));
                $template->setValue("telcliente{$i}", $c->celular ?? $c->telefone ?? '');
                $template->setValue("catcliente{$i}", $item->categoria);
                
                // AQUI ESTÁ A CORREÇÃO:
                // Preenchemos a variável da linha específica (ex: periodoaula1) com o valor GLOBAL
                // Se não houver aluno, cai no else e limpa a variável.
                $template->setValue("periodoaula{$i}", $oficio->periodo_aula); 
            } else {
                // Limpa slot vazio
                $template->setValue("nomecliente{$i}", "");
                $template->setValue("cpfcliente{$i}", "");
                $template->setValue("telcliente{$i}", "");
                $template->setValue("catcliente{$i}", "");
                $template->setValue("periodoaula{$i}", ""); // Limpa o período dessa linha
            }
        }

        // --- 5. INSTRUTORES (Múltiplos) ---
        $instrutores = $oficio->instrutores_oficio()->with('prestador')->get();
        
        // Acha o principal para assinar
        $principal = $instrutores->where('is_principal', true)->first();
        if (!$principal && $instrutores->count() > 0) {
            $principal = $instrutores->first(); // Fallback
        }

        // Assinatura (Rodapé)
        $template->setValue('nomeinstrutorprincipal', $principal ? $this->up($principal->prestador->nome) : '');

        // Lista de Instrutores (Até 4 slots na tabela)
        for ($j = 1; $j <= 4; $j++) {
            if (isset($instrutores[$j-1])) {
                $p = $instrutores[$j-1]->prestador;
                
                $template->setValue("nomeinstrutor{$j}", $this->up($p->nome));
                $template->setValue("cpfinstrutor{$j}", $this->formatarCpfCnpj($p->cpfcnpj));
                $template->setValue("celinstrutor{$j}", $p->celular ?? '');
                $template->setValue("chainstrutor{$j}", $p->cha_numero ?? '');
            } else {
                $template->setValue("nomeinstrutor{$j}", "");
                $template->setValue("cpfinstrutor{$j}", "");
                $template->setValue("celinstrutor{$j}", "");
                $template->setValue("chainstrutor{$j}", "");
            }
        }

        // --- SALVAR ---
        $safeNum = str_replace('/', '-', $oficio->numero_oficio);
        return $this->salvarEConverter($template, "Oficio_{$safeNum}");
    }

    // --- Métodos Auxiliares ---
    private function salvarEConverter(TemplateProcessor $template, $filenameBase) {
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);

        $outputDir = storage_path("app/public/documentos_gerados");
        if (!file_exists($outputDir)) mkdir($outputDir, 0755, true);

        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "{$filenameBase}.docx";
        $template->saveAs($tempDocx);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "{$filenameBase}.pdf";
        if (file_exists($pdfPath)) @unlink($pdfPath);

        // Ajuste para Linux/Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $libreOfficePath = '"C:\Program Files\LibreOffice\program\soffice.exe"';
            $command = $libreOfficePath . ' --headless --convert-to pdf "' . $tempDocx . '" --outdir "' . $outputDir . '"';
        } else {
            $command = "export HOME=/tmp && libreoffice --headless --convert-to pdf " . escapeshellarg($tempDocx) . " --outdir " . escapeshellarg($outputDir);
        }

        shell_exec($command . " 2>&1");
        
        @unlink($tempDocx);
        
        if (!file_exists($pdfPath)) {
            throw new \Exception("Erro ao gerar PDF.");
        }

        return $pdfPath;
    }

    private function up($valor) { return mb_strtoupper((string) ($valor ?? ''), 'UTF-8'); }

    private function formatarCpfCnpj($valor) {
        $valor = preg_replace('/[^0-9]/', '', (string) $valor);
        if (strlen($valor) === 11) return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
        if (strlen($valor) === 14) return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
        return $valor;
    }
}