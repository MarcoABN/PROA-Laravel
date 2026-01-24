<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\ProcuracaoService;
use Illuminate\Http\Request;
use App\Services\DefesaInfracaoService;

class ClienteController extends Controller
{
    public function imprimirProcuracao($id, $embarcacao_id = null, ProcuracaoService $service)
    {
        $cliente = Cliente::findOrFail($id);

        // Trata o parâmetro caso venha como string "null" da URL
        $embarcacaoId = ($embarcacao_id && $embarcacao_id !== 'null') ? (int) $embarcacao_id : null;

        try {
            $pdfPath = $service->gerarProcuracao02($cliente, $embarcacaoId);

            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="procuracao-' . $cliente->nome . '.pdf"'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function imprimirDefesa(Request $request, $id, $embarcacao_id = null, DefesaInfracaoService $service)
    {
        $cliente = Cliente::findOrFail($id);
        $embarcacaoId = ($embarcacao_id && $embarcacao_id !== 'null') ? (int) $embarcacao_id : null;

        // Pega os dados extras passados via Query String (GET)
        $dadosForm = $request->all();

        try {
            $pdfPath = $service->gerarDefesa($cliente, $embarcacaoId, $dadosForm);

            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="defesa-infracao.pdf"'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}