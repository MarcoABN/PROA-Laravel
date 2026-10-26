<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Opções de um mês de atendimento (competência) do Agendamento Marinha.
 * Mês sem linha aqui fica com o padrão (não exibido na extensão, salvo pela regra automática).
 */
class MesAgendamento extends Model
{
    protected $table = 'agendamento_meses';

    protected $guarded = [];

    protected $casts = [
        'competencia' => 'date',
        'exibir_na_extensao' => 'boolean',
    ];

    public static function exibirNaExtensao(mixed $competencia): bool
    {
        return (bool) static::whereDate('competencia', static::competencia($competencia))->value('exibir_na_extensao');
    }

    public static function definirExibirNaExtensao(mixed $competencia, bool $exibir): void
    {
        static::updateOrCreate(['competencia' => static::competencia($competencia)], ['exibir_na_extensao' => $exibir]);
    }

    private static function competencia(mixed $valor): string
    {
        return Carbon::parse($valor)->startOfMonth()->toDateString();
    }
}
