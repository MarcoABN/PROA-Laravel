<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\SiteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/admin');
});

// --- ROTA CORRIGIDA ---
// Aponta para o Controller que sabe decidir entre PDF e DOCX
Route::get('/admin/anexos/gerar/{classe}/{embarcacao}', [AnexoController::class, 'gerarGenerico'])
    ->middleware('auth')
    ->name('anexos.gerar_generico');

// Rota de Propostas
Route::get('/propostas/{id}/imprimir', [PropostaController::class, 'imprimir'])
    ->middleware('auth')
    ->name('propostas.imprimir');

Route::get('/', [App\Http\Controllers\SiteController::class, 'index']);
// Comente a rota anterior e use esta:
/*Route::get('/', function () {
    return view('site.index');
});*/