<?php

namespace App\Http\Controllers;

use App\Models\Servico; // Certifique-se de que a model existe
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        $servicos = \App\Models\Servico::where('ativo', true)->get();
        return view('site.index', compact('servicos'));
    }
}