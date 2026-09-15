<?php

use App\Http\Controllers\Api\SisapAgendamentoController;
use App\Http\Middleware\AutenticaProcuradorSisap;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API da extensão de agendamento (SISAP / Marinha)
|--------------------------------------------------------------------------
| Authorization: Bearer <token>. Com token de usuário do PROA, o procurador vem do CPF logado
| no SISAP, enviado pela extensão no cabeçalho X-Procurador-Cpf.
*/

Route::prefix('sisap')
    ->middleware([AutenticaProcuradorSisap::class, 'throttle:120,1'])
    ->group(function () {
        Route::get('eu', [SisapAgendamentoController::class, 'eu']);
        Route::get('agendamentos', [SisapAgendamentoController::class, 'index']);
        Route::post('agendamentos/{agendamento}/escolher-horario', [SisapAgendamentoController::class, 'escolherHorario']);
        Route::post('agendamentos/{agendamento}/resultado', [SisapAgendamentoController::class, 'resultado']);
        Route::post('agendamentos/{agendamento}/falha', [SisapAgendamentoController::class, 'falha']);
    });
