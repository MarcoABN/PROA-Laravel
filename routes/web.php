<?php

use App\Http\Controllers\ClienteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\PreferenciaController;
use App\Http\Controllers\SiteController;
use App\Livewire\Auth\LoginCpf;
use App\Livewire\SimuladoNaval;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Rota Pública (Site)
Route::get('/', [SiteController::class, 'index'])->name('site.index');
Route::get('/servicos/{servico:slug}', [SiteController::class, 'servico'])->name('site.servico');
Route::get('/blog', [SiteController::class, 'blog'])->name('site.blog');
Route::get('/blog/{artigo:slug}', [SiteController::class, 'artigo'])->name('site.artigo');

// Sitemap gerado na hora a partir dos serviços ativos. Não manter um public/sitemap.xml
// no servidor: o Apache serve o arquivo estático no lugar desta rota.
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('site.sitemap');

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

    // Posição do menu do painel (lateral ou superior)
    Route::get('/preferencias/menu/{posicao}', [PreferenciaController::class, 'navegacao'])
        ->name('preferencias.menu');
});


Route::get('/propostas/{id}/imprimir', [PropostaController::class, 'imprimir'])->name('propostas.imprimir');

// ADICIONE ESTA LINHA (Rota do Recibo):
Route::get('/propostas/{id}/imprimir-recibo', [PropostaController::class, 'imprimirRecibo'])->name('propostas.imprimir_recibo');

Route::get('/clientes/{id}/procuracao/{embarcacao_id?}', [ClienteController::class, 'imprimirProcuracao'])
    ->name('clientes.procuracao')
    ->middleware('auth');

Route::get('/clientes/{id}/defesa-infracao/{embarcacao_id?}', [ClienteController::class, 'imprimirDefesa'])
    ->name('clientes.defesa_infracao')
    ->middleware('auth');


Route::get('/googlefab17170d240591b.html', function () {
    return 'google-site-verification: googlefab17170d240591b.html';
});