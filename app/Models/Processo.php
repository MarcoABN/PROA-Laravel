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

    /**
     * Lógica Automática (Audit Trail)
     * Monitora alterações em campos críticos e gera histórico.
     */
    protected static function booted()
    {
        static::updating(function ($processo) {
            $user = Auth::user();
            if (!$user) return; // Se for rodado via sistema/cron sem usuário, ignora ou ajusta

            // Mapa de Labels para ficar bonito no texto (Ex: 'analise' vira 'Em Análise')
            $labels = [
                'status' => [
                    'triagem' => 'Triagem', 'analise' => 'Em Análise', 'aguardando_cliente' => 'Aguardando Cliente',
                    'protocolado' => 'Protocolado', 'exigencia' => 'Com Exigência', 'concluido' => 'Concluído', 'arquivado' => 'Arquivado'
                ],
                'prioridade' => [
                    'baixa' => 'Baixa', 'normal' => 'Normal', 'alta' => 'Alta', 'urgente' => 'Urgente'
                ]
            ];

            // 1. Monitora Status
            if ($processo->isDirty('status')) {
                $antigo = $labels['status'][$processo->getOriginal('status')] ?? $processo->getOriginal('status');
                $novo = $labels['status'][$processo->status] ?? $processo->status;
                
                $processo->andamentos()->create([
                    'user_id' => $user->id,
                    'tipo' => 'mudanca_status', // Usamos um tipo específico para destacar visualmente se quiser depois
                    'descricao' => "Alterou o Status de '{$antigo}' para '{$novo}'.",
                ]);
            }

            // 2. Monitora Prioridade
            if ($processo->isDirty('prioridade')) {
                $antigo = $labels['prioridade'][$processo->getOriginal('prioridade')] ?? $processo->getOriginal('prioridade');
                $novo = $labels['prioridade'][$processo->prioridade] ?? $processo->prioridade;

                $processo->andamentos()->create([
                    'user_id' => $user->id,
                    'tipo' => 'comentario',
                    'descricao' => "Alterou a Prioridade de '{$antigo}' para '{$novo}'.",
                ]);
            }

            // 3. Monitora Prazo
            if ($processo->isDirty('prazo_estimado')) {
                // Formata datas para d/m/Y para ficar legível
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