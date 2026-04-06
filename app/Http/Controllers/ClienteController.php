<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\ProcuracaoService;
use Illuminate\Http\Request;
use App\Services\DefesaInfracaoService;

class ClienteController extends Controller
{
    // 1º Service, 2º ID obrigatório, 3º ID opcional
    public function imprimirProcuracao(ProcuracaoService $service, $id, $embarcacao_id = null)
    {
        $cliente = Cliente::findOrFail($id);

        // O seu tratamento continua perfeito aqui
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

    // 1º Request, 2º Service, 3º ID obrigatório, 4º ID opcional
    public function imprimirDefesa(Request $request, DefesaInfracaoService $service, $id, $embarcacao_id = null)
    {
        $cliente = Cliente::findOrFail($id);
        $embarcacaoId = ($embarcacao_id && $embarcacao_id !== 'null') ? (int) $embarcacao_id : null;

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