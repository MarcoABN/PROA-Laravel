<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use App\Services\PropostaDocService;
use Illuminate\Http\Response;

class PropostaController extends Controller
{
    public function imprimir($id, PropostaDocService $service)
    {
        $proposta = Proposta::findOrFail($id);
        
        // Gera o PDF
        $pdfPath = $service->gerarPdf($proposta);

        // Retorna o arquivo para o navegador exibir (inline) ao invés de baixar
        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="proposta-'.$proposta->numero_formatado.'.pdf"'
        ]);
    }
}