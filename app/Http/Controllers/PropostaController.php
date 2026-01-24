<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use App\Services\PropostaDocService;
use Illuminate\Http\Response;

class PropostaController extends Controller
{
    // Gera o CONTRATO
    public function imprimir($id, PropostaDocService $service)
    {
        $proposta = Proposta::findOrFail($id);
        
        $pdfPath = $service->gerarPdf($proposta);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contrato-'.$proposta->numero_formatado.'.pdf"'
        ]);
    }

    // Gera o RECIBO
    public function imprimirRecibo($id, PropostaDocService $service)
    {
        $proposta = Proposta::findOrFail($id);
        
        $pdfPath = $service->gerarReciboPdf($proposta);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="recibo-'.$proposta->numero_formatado.'.pdf"'
        ]);
    }
}