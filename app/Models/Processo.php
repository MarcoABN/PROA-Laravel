<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Processo extends Model
{
    protected $guarded = [];

    // Constantes para padronização dos tipos de serviço
    const TIPO_CHA = 'Habilitação (CHA)';
    const TIPO_TIE = 'Embarcação (TIE)';
    const TIPO_MOTO = 'Motoaquática (TIE)';
    const TIPO_DEFESA = 'Defesa De Infração';
    const TIPO_OUTROS = 'Outros';

    protected $casts = [
        'prazo_estimado' => 'date',
    ];

    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class); }
    public function embarcacao(): BelongsTo { return $this->belongsTo(Embarcacao::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    
    public function andamentos(): HasMany 
    { 
        return $this->hasMany(ProcessoAndamento::class)->latest(); 
    }

    protected static function booted()
    {
        static::updating(function ($processo) {
            $user = Auth::user();
            if (!$user) return;

            $labels = [
                'status' => [
                    'triagem' => 'Triagem', 'analise' => 'Em Análise', 'aguardando_cliente' => 'Aguardando Cliente',
                    'protocolado' => 'Protocolado', 'exigencia' => 'Com Exigência', 'concluido' => 'Concluído', 'arquivado' => 'Arquivado'
                ],
                'prioridade' => [
                    'baixa' => 'Baixa', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'
                ]
            ];

            if ($processo->isDirty('status')) {
                $antigo = $labels['status'][$processo->getOriginal('status')] ?? $processo->getOriginal('status');
                $novo = $labels['status'][$processo->status] ?? $processo->status;
                
                $processo->andamentos()->create([
                    'user_id' => $user->id,
                    'tipo' => 'mudanca_status',
                    'descricao' => "Alterou o Status de '{$antigo}' para '{$novo}'.",
                ]);
            }

            if ($processo->isDirty('prioridade')) {
                $antigo = $labels['prioridade'][$processo->getOriginal('prioridade')] ?? $processo->getOriginal('prioridade');
                $novo = $labels['prioridade'][$processo->prioridade] ?? $processo->prioridade;

                $processo->andamentos()->create([
                    'user_id' => $user->id,
                    'tipo' => 'comentario',
                    'descricao' => "Alterou a Prioridade de '{$antigo}' para '{$novo}'.",
                ]);
            }

            if ($processo->isDirty('prazo_estimado')) {
                $antigo = $processo->getOriginal('prazo_estimado') ? Carbon::parse($processo->getOriginal('prazo_estimado'))->format('d/m/Y') : 'Não definido';
                $novo = $processo->prazo_estimado ? Carbon::parse($processo->prazo_estimado)->format('d/m/Y') : 'Não definido';

                $processo->andamentos()->create([
                    'user_id' => $user->id,
                    'tipo' => 'comentario',
                    'descricao' => "Alterou o Prazo Estimado de '{$antigo}' para '{$novo}'.",
                ]);
            }
        });
    }
}