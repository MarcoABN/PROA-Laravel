<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\SiteController;
use App\Livewire\Auth\LoginCpf;
use App\Livewire\SimuladoNaval;
use Spatie\Sitemap\SitemapGenerator;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Rota Pública (Site)
Route::get('/', [SiteController::class, 'index'])->name('site.index');

// 2. Rota de Login do Cliente (CPF)
Route::get('/login', LoginCpf::class)->name('login');

// 3. Área do CLIENTE (Aluno)
Route::middleware(['auth:cliente'])->group(function () {
    
    // Dashboard do Aluno
    Route::get('/cliente/dashboard', function () {
        return view('dashboard'); 
    })->name('cliente.dashboard');

    // Simulado do Aluno (COM PARÂMETRO DE MODALIDADE)
    Route::get('/cliente/simulado/{modalidade}', SimuladoNaval::class)
        ->name('cliente.simulado');
});

// 4. Área do ADMIN (Rotas Extras do PROA)
Route::middleware(['auth:web'])->prefix('admin')->group(function () {
    
    // Gerador de Anexos
    Route::get('/anexos/gerar/{classe}/{embarcacao}', [AnexoController::class, 'gerarGenerico'])
        ->name('anexos.gerar_generico');
});

// Outras rotas protegidas gerais
Route::middleware(['auth:web'])->group(function () {
    Route::get('/propostas/{id}/imprimir', [PropostaController::class, 'imprimir'])
        ->name('propostas.imprimir');
});

Route::get('/gerar-sitemap', function () {
    SitemapGenerator::create('https://campeaonautica.com.br')->writeToFile(public_path('sitemap.xml'));
    return 'Sitemap gerado com sucesso!';
});