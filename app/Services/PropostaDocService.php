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
        // 1. Validação e Carregamento do Template
        $templatePath = storage_path('app/public/templates/CBS-PRS-04560-130126.docx');
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template não encontrado em: $templatePath");
        }

        $template = new TemplateProcessor($templatePath);
        
        // Carregando relacionamentos
        $escola = $proposta->escola;
        $cliente = $proposta->cliente;
        $embarcacao = $proposta->embarcacao;
        $responsavel = $escola->responsavel; 

        // 2. Cabeçalho (Logo)
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

        // 3. Dados Gerais
        $template->setValue('numero_proposta', $proposta->numero_formatado);
        
        // Data e Local em CAIXA ALTA
        Carbon::setLocale('pt_BR');
        $dataCarbon = Carbon::parse($proposta->data_proposta);
        
        $cidadeUf = $escola->cidade ?? 'Brasília';
        if (!empty($escola->uf)) {
            $cidadeUf .= '/' . $escola->uf;
        }
        
        $dataExtenso = $cidadeUf . ', ' . $dataCarbon->translatedFormat('d \d\e F \d\e Y');
        $template->setValue('local_data_topo',$dataExtenso);

        // 4. Dados da Escola
        $template->setValue('proponente_topo', $this->up($escola->razao_social));
        $template->setValue('cnpj_proponente_topo', $this->formatarCpfCnpj($escola->cnpj));
        $template->setValue('cidade_proponente', $this->up($cidadeUf));

        // --- RODAPÉ SEM CAIXA ALTA E SEM HTTPS ---
        
        // Tratamento do Site: Remove http://, https:// e barras no final
        $siteLimpo = $escola->site ?? '';
        $siteLimpo = str_ireplace(['https://', 'http://'], '', $siteLimpo);
        $siteLimpo = rtrim($siteLimpo, '/');
        
        // Note que aqui NÃO usamos o $this->up()
        $template->setValue('site_proponente', $siteLimpo); 
        $template->setValue('email_proponente', $escola->email_contato ?? ''); // Sem up()
        
        // Contatos
        $contatos = $escola->telefone_responsavel ?? '';
        if (!empty($escola->telefone_secundario)) {
            if (!empty($contatos)) {
                $contatos .= ' / ';
            }
            $contatos .= $escola->telefone_secundario;
        }
        $template->setValue('contato_proponente', $contatos); // Sem up()
        $template->setValue('contato_proponente2', '');

        // Responsável (Nome em Caixa Alta)
        if ($responsavel) {
            $cpfRaw = $responsavel->cpfcnpj ?? ''; 
            $cpfFormatado = $this->formatarCpfCnpj($cpfRaw);
            $textoResp = "{$responsavel->nome} - CPF {$cpfFormatado}";
            $template->setValue('responsavel_escolanautica', $this->up($textoResp));
        } else {
            $template->setValue('responsavel_escolanautica', '');
        }

        // Dados Bancários (Caixa Alta mantida)
        $template->setValue('banco_proponente', $this->up($escola->banco));
        $template->setValue('ag_proponente', $this->up($escola->agencia));
        $template->setValue('cc_proponente', $this->up($escola->conta_corrente));
        $template->setValue('pix_proponente', $this->up($escola->chave_pix));

        // 5. Dados do Cliente (Caixa Alta mantida)
        $template->setValue('cliente_aceitante', $this->up($cliente->nome));
        
        $enderecoCompleto = ($cliente->logradouro ?? '') . ', ' . ($cliente->numero ?? '');
        $template->setValue('endereco_aceitante', $this->up($enderecoCompleto));
        
        $template->setValue('cnpj_aceitante', $this->formatarCpfCnpj($cliente->cpfcnpj));

        // 6. Dados da Embarcação (Caixa Alta mantida)
        if ($embarcacao) {
            $template->setValue('nome_embarcacao', $this->up($embarcacao->nome_embarcacao ?? 'N/A'));
            $template->setValue('tipo_embarcacao', $this->up($embarcacao->tipo_embarcacao ?? 'N/A'));
            $template->setValue('comp_embarcacao', $embarcacao->comp_total ? $embarcacao->comp_total . 'm' : 'N/A');
        } else {
            $template->setValue('nome_embarcacao', 'N/A');
            $template->setValue('tipo_embarcacao', 'N/A');
            $template->setValue('comp_embarcacao', 'N/A');
        }
        $template->setValue('ab', '0');

        // 7. Tabela de Serviços (Caixa Alta mantida)
        $itens = $proposta->itens_servico ?? [];
        $totalBruto = 0;

        for ($i = 1; $i <= 11; $i++) {
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

        // Totais
        $desconto = (float) $proposta->valor_desconto;
        $totalLiquido = $totalBruto - $desconto;

        $template->setValue('valorbrutoserv', 'R$ ' . number_format($totalBruto, 2, ',', '.'));
        $template->setValue('descserv', 'R$ ' . number_format($desconto, 2, ',', '.'));
        $template->setValue('valortotalserv', 'R$ ' . number_format($totalLiquido, 2, ',', '.'));

        // 8. Parcelamento (Caixa Alta mantida)
        $parcelas = $proposta->parcelas ?? [];
        $qtdParcelas = count($parcelas);

        $extensoMap = [
            0 => 'À VISTA',
            1 => 'UMA PARCELA',
            2 => 'DUAS PARCELAS',
            3 => 'TRÊS PARCELAS',
            4 => 'QUATRO PARCELAS'
        ];
        $textoExtenso = $extensoMap[$qtdParcelas] ?? "{$qtdParcelas} PARCELAS";
        
        $template->setValue('qtd_parcela_extenso', $this->up($textoExtenso));

        for ($i = 1; $i <= 4; $i++) {
            $index = $i - 1;
            
            if (isset($parcelas[$index])) {
                $p = $parcelas[$index];
                $valorP = (float) $p['valor'];
                $descP = $p['descricao'];
                
                $template->setValue("parc{$i}", "{$i} / {$qtdParcelas}");
                $template->setValue("descparc{$i}", $this->up($descP));
                $template->setValue("valorparc{$i}", 'R$ ' . number_format($valorP, 2, ',', '.'));
            } else {
                $template->setValue("parc{$i}", '');
                $template->setValue("descparc{$i}", '');
                $template->setValue("valorparc{$i}", '');
            }
        }

        // 9. Geração dos Arquivos
        $tempDir = storage_path("app/public/temp");
        if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

        $tempDocx = $tempDir . DIRECTORY_SEPARATOR . "proposta_{$proposta->id}.docx";
        if (file_exists($tempDocx)) @unlink($tempDocx);
        
        $template->saveAs($tempDocx);

        $outputDir = storage_path("app/public/propostas_pdf");
        if (!file_exists($outputDir)) mkdir($outputDir, 0777, true);

        $pdfPath = $outputDir . DIRECTORY_SEPARATOR . "proposta_{$proposta->id}.pdf";
        if (file_exists($pdfPath)) @unlink($pdfPath);

        try {
            $word = new \COM("Word.Application") or die("Erro ao instanciar o Word.");
            $word->Visible = 0;
            $word->DisplayAlerts = 0;
            $doc = $word->Documents->Open(realpath($tempDocx));
            $doc->ExportAsFixedFormat($pdfPath, 17);
            $doc->Close(false);
            $word->Quit();
            $word = null;
        } catch (\Throwable $e) {
            if (isset($word)) {
                $word->Quit();
                $word = null;
            }
            throw new \Exception("Erro na conversão: " . $e->getMessage());
        }

        return $pdfPath;
    }

    // --- HELPER PARA CAIXA ALTA (UTF-8) ---
    private function up($valor)
    {
        return mb_strtoupper((string)($valor ?? ''), 'UTF-8');
    }

    private function formatarCpfCnpj($valor)
    {
        $valor = preg_replace('/[^0-9]/', '', (string)$valor);
        
        if (strlen($valor) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $valor);
        } elseif (strlen($valor) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $valor);
        }
        
        return $valor; 
    }
}