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
        $this->definir($template,'nomecliente', $this->up($cliente->nome));

        // Concatena endereço do cliente
        $endCliente = ($cliente->logradouro ?? '') . ', ' . ($cliente->numero ?? '');
        $this->definir($template,'enderecocliente', $this->up($endCliente));

        $this->definir($template,'cep', $cliente->cep);
        $this->definir($template,'cidade', $this->up($cliente->cidade)); // Cidade no endereço
        $this->definir($template,'bairro', $this->up($cliente->bairro));
        $this->definir($template,'rg', $cliente->rg ?? '');
        $this->definir($template,'orgexpedidor', $this->up($cliente->org_emissor ?? ''));
        $this->definir($template,'cpfcliente', $this->formatarCpfCnpj($cliente->cpfcnpj));
        $this->definir($template,'email', strtolower($cliente->email));
        $this->definir($template,'celular', $cliente->celular);

        // --- 3. DADOS DA EMBARCAÇÃO (Condicional) ---
        if ($embarcacao) {
            $this->definir($template,'label_embarcacao', 'NOME DA EMBARCAÇÃO:');
            $this->definir($template,'nome_embarcacao', $this->up($embarcacao->nome_embarcacao));
        } else {
            // Limpa os campos se não houver embarcação
            $this->definir($template,'label_embarcacao', '');
            $this->definir($template,'nome_embarcacao', '');
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
        $this->definir($template,'local_data', $dataExtenso);

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

            // Procurador pode ser pessoa física (CPF) ou jurídica (CNPJ, 14 dígitos).
            // A redação da procuração muda conforme o caso.
            $ehPessoaJuridica = strlen(preg_replace('/[^0-9]/', '', (string) $proc->cpfcnpj)) === 14;

            // Monta o RG com Órgão Emissor se houver
            $rgTexto = $proc->rg;
            if (!empty($proc->org_emissor)) {
                $rgTexto .= ' ' . $proc->org_emissor;
            }

            if ($ehPessoaJuridica) {
                $tratamento = $this->up($proc->nome);
                $qualificacao = "inscrita no CNPJ sob o nº {$cpf}";
            } else {
                $tratamento = "Sr. " . $this->up($proc->nome);
                $qualificacao = "portador da Carteira de Identidade nº {$rgTexto} e CPF {$cpf}";
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

                if ($ehPessoaJuridica) {
                    // Pessoa jurídica não tem nacionalidade/estado civil/profissão e tem sede, não domicílio
                    $partes = [
                        $tratamento,
                        "pessoa jurídica de direito privado",
                        $qualificacao,
                        "com sede na " . ($enderecoProc ?? 'endereço não informado')
                    ];
                } else {
                    $partes = [
                        $tratamento,
                        $proc->nacionalidade ?? 'brasileiro',
                        $proc->estado_civil,
                        $proc->profissao,
                        $qualificacao,
                        "residente e domiciliado na " . ($enderecoProc ?? 'endereço não informado')
                    ];
                }

                // Filtra campos vazios e une com vírgula
                $textosCompletos[] = implode(', ', array_filter($partes));
            } else {
                // Tipo REDUZIDO
                $partes = [
                    $tratamento,
                    $qualificacao
                ];
                $textosReduzidos[] = implode(', ', array_filter($partes));
            }
        }

        // Insere no template com gramática correta (A, B e C)
        $this->definir($template,'procuradores_completo', $this->listarGramaticalmente($textosCompletos));
        $this->definir($template,'procuradores_reduzido', $this->listarGramaticalmente($textosReduzidos));
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

        // Remove arquivo antigo se existir
        if (file_exists($pdfPath))
            @unlink($pdfPath);

        // 4. CONVERSÃO COM CORREÇÃO DE HOME (Truque para Linux/www-data)
        // O comando exporta o HOME para /tmp antes de rodar o LibreOffice
        // Isso evita que ele tente escrever em /var/www/.cache e falhe
        $command = "export HOME=/tmp && libreoffice --headless --convert-to pdf " . escapeshellarg($tempDocx) . " --outdir " . escapeshellarg($outputDir);

        $output = shell_exec($command . " 2>&1");

        // 5. Verificação
        if (!file_exists($pdfPath)) {
            @unlink($tempDocx); // Limpa lixo
            throw new \Exception("Erro ao gerar PDF da Procuração. Log: " . $output);
        }

        // Limpeza do arquivo temporário
        @unlink($tempDocx);

        return $pdfPath;
    }

    /**
     * Grava um valor no template escapando os caracteres reservados do XML.
     *
     * O PhpWord tem escaping próprio, mas ele vem desligado por padrão
     * (Settings::$outputEscapingEnabled = false), então o valor era injetado cru no
     * document.xml: uma embarcação chamada "MAR & SOL" gerava XML inválido
     * ("xmlParseEntityRef: no name") e o documento não abria.
     *
     * Não ligamos o escaping global do PhpWord de propósito: outros pontos do
     * sistema dependem de injetar marcação própria (por exemplo <w:br/> para
     * quebras de linha), que o modo global escaparia junto.
     */
    private function definir(TemplateProcessor $template, string $campo, $valor): void
    {
        $template->setValue($campo, $this->escaparXml($valor));
    }

    private function escaparXml($valor): string
    {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
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