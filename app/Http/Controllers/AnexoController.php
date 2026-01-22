<?php

namespace App\Http\Controllers;

use App\Services\AnexoService;
use App\Services\AnexoDocService;
use Illuminate\Http\Request;

class AnexoController extends Controller
{
    public function gerarGenerico(Request $request, $classe, $id)
    {
        $classeReal = str_replace('-', '\\', $classe);
        
        if (!class_exists($classeReal)) {
            abort(404, "Classe de anexo não encontrada: $classeReal");
        }

        // --- LÓGICA DE SELEÇÃO: CLIENTE OU EMBARCAÇÃO ---
        if ($request->query('tipo') === 'cliente') {
            $model = \App\Models\Cliente::findOrFail($id);
        } else {
            $model = \App\Models\Embarcacao::findOrFail($id);
        }

        $anexoInstance = new $classeReal();
        $templatePath = $anexoInstance->getTemplatePath();
        $extensao = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));

        if ($extensao === 'docx') {
            $service = new AnexoDocService();
            // Passa o $model (seja ele Cliente ou Embarcacao)
            $caminhoArquivo = $service->gerarAnexoDocx($model, $anexoInstance, $request->all());
            
            return response()->file($caminhoArquivo, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="documento.pdf"'
            ]);
        } else {
            $service = new AnexoService();
            // Passa o $model
            $conteudoBinario = $service->gerarPdf($anexoInstance, $model, $request->all());

            return response($conteudoBinario, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="documento.pdf"',
            ]);
        }
    }
}