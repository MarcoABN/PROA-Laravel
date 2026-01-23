<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\SiteController;
use App\Livewire\Auth\LoginCpf;
use App\Livewire\SimuladoNaval;


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

Route::get('/login', \App\Livewire\Auth\LoginCpf::class)->name('login');

Route::middleware(['auth'])->group(function () {
    // Tela de seleção (Dashboard)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Componente do Simulado com parâmetro dinâmico
    Route::get('/simulado/{modalidade}', SimuladoNaval::class)->name('simulado');
});