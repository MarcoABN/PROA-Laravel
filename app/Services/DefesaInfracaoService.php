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
        if ($cliente->numero)
            $endCompleto .= ', ' . $cliente->numero;
        if ($cliente->complemento)
            $endCompleto .= ' ' . $cliente->complemento;

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