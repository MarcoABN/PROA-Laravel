<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Embarcacao;
use App\Models\Capitania;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;

class DefesaInfracaoService
{
    public function gerarDefesa(Cliente $cliente, ?int $embarcacaoId, array $dadosForm)
    {
        $templatePath = storage_path('app/public/templates/modelo_defesa.docx');
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template 'modelo_defesa.docx' não encontrado.");
        }

        $template = new TemplateProcessor($templatePath);
        $embarcacao = $embarcacaoId ? Embarcacao::find($embarcacaoId) : null;

        // --- 1. DADOS ESPECÍFICOS ---
        
        // Capitania
        if (isset($dadosForm['capitania_id'])) {
            $capitania = Capitania::find($dadosForm['capitania_id']);
            $template->setValue('capitania', $this->up($capitania->nome ?? ''));
        } else {
            $template->setValue('capitania', '');
        }

        $template->setValue('num_notificacao', $dadosForm['num_notificacao'] ?? '');
        $template->setValue('justificativa', $dadosForm['justificativa'] ?? '');
        
        $dataNotificacao = $dadosForm['data_notificacao'] ?? null;
        $template->setValue('data_notificacao', $dataNotificacao ? Carbon::parse($dataNotificacao)->format('d/m/Y') : '');

        // --- 2. DADOS DO CLIENTE ---
        $template->setValue('nome_cliente', $this->up($cliente->nome));
        $template->setValue('cpf_cliente', $this->formatarCpfCnpj($cliente->cpfcnpj));
        $template->setValue('rg_cliente', $cliente->rg ?? '');
        $template->setValue('org_emissor', $this->up($cliente->org_emissor));
        
        $dtEmissao = $cliente->dt_emissao ? Carbon::parse($cliente->dt_emissao)->format('d/m/Y') : '';
        $template->setValue('data_emissaorg', $dtEmissao);

        $template->setValue('num_habilitacao', $cliente->cha_numero ?? '');

        // Endereço Cliente
        $endCompleto = $cliente->logradouro;
        if($cliente->numero) $endCompleto .= ', ' . $cliente->numero;
        if($cliente->complemento) $endCompleto .= ' ' . $cliente->complemento;

        $template->setValue('endereco_cliente', $this->up($endCompleto));
        $template->setValue('bairro_cliente', $this->up($cliente->bairro));
        $template->setValue('cidade_cliente', $this->up($cliente->cidade . '/' . $cliente->uf));
        $template->setValue('cep_cliente', $cliente->cep);
        
        $template->setValue('tel_cliente', $cliente->telefone ?? '');
        $template->setValue('cel_cliente', $cliente->celular ?? '');
        $template->setValue('email_cliente', strtolower($cliente->email ?? ''));

        // --- 3. DADOS DA EMBARCAÇÃO ---
        if ($embarcacao) {
            $template->setValue('nome_embarcacao', $this->up($embarcacao->nome_embarcacao));
            $template->setValue('num_inscricao', $embarcacao->numero_inscricao ?? $embarcacao->tie ?? '');
        } else {
            $template->setValue('nome_embarcacao', '---');
            $template->setValue('num_inscricao', '---');
        }

        // --- 4. LOCAL E DATA (Atualizado) ---
        Carbon::setLocale('pt_BR');
        
        // Define Cidade e UF
        if ($embarcacao && !empty($embarcacao->cidade)) {
            $cidade = $embarcacao->cidade;
            $uf = $embarcacao->uf ?? ''; 
        } else {
            $cidade = $cliente->cidade;
            $uf = $cliente->uf;
        }

        // Formata: CIDADE - UF
        $local = $this->up($cidade);
        if (!empty($uf)) {
            $local .= ' - ' . $this->up($uf);
        }

        // Formata Final: CIDADE - UF, em 01 de Janeiro de 2026
        $dataExtenso = $local . ', em ' . Carbon::now()->translatedFormat('d \d\e F \d\e Y');
        $template->setValue('local_data', $dataExtenso);

        // --- 5. GERAR ---
        $fileName = "defesa_{$cliente->id}_" . time();
        return $this->salvarEConverter($template, $fileName);
    }

    private function salvarEConverter(TemplateProcessor $template, $filenameBase)
    {
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "{$filenameBase}.docx";
        if (file_exists($tempDocx)) @unlink($tempDocx);

        $template->saveAs($tempDocx);

        $outputDir = storage_path("app/public/documentos_gerados");
        if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "{$filenameBase}.pdf";
        if (file_exists($pdfPath)) @unlink($pdfPath);

        try {
            $word = new \COM("Word.Application") or die("Erro Word");
            $word->Visible = 0; $word->DisplayAlerts = 0;
            $doc = $word->Documents->Open(realpath($tempDocx));
            $doc->ExportAsFixedFormat($pdfPath, 17);
            $doc->Close(false); $word->Quit(); $word = null;
        } catch (\Throwable $e) {
            if (isset($word)) { $word->Quit(); $word = null; }
            throw new \Exception("Erro PDF: " . $e->getMessage());
        }

        return $pdfPath;
    }

    private function up($v) { return mb_strtoupper((string)($v ?? ''), 'UTF-8'); }
    private function formatarCpfCnpj($v) {
        $v = preg_replace('/[^0-9]/', '', (string)$v);
        if (strlen($v) == 11) return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $v);
        if (strlen($v) == 14) return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $v);
        return $v; 
    }
}