<?php

namespace App\Models;

use App\Models\Concerns\RegistraLogFinanceiro;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class LancamentoFinanceiro extends Model
{
    use RegistraLogFinanceiro;

    protected $table = 'lancamentos_financeiros';

    protected $guarded = [];

    const TIPO_ENTRADA = 'entrada';
    const TIPO_SAIDA   = 'saida';

    const ESCOPO_USUARIO = 'usuario';
    const ESCOPO_EMPRESA = 'empresa';

    const PAGAMENTO_DINHEIRO = 'Dinheiro';
    const PAGAMENTO_PIX      = 'Pix';
    const PAGAMENTO_CARTAO   = 'Cartão';

    protected $casts = [
        'valor' => 'decimal:2',
        'data_lancamento' => 'date',
        'anexos' => 'array',
    ];

    public static function tipos(): array
    {
        return [
            self::TIPO_ENTRADA => 'Entrada',
            self::TIPO_SAIDA   => 'Saída',
        ];
    }

    public static function escopos(): array
    {
        return [
            self::ESCOPO_USUARIO => 'Usuário',
            self::ESCOPO_EMPRESA => 'Empresa',
        ];
    }

    /**
     * O próprio texto é a chave: o valor gravado já é o que se lê na tela,
     * então relatório e histórico antigos continuam legíveis.
     */
    public static function formasPagamento(): array
    {
        return [
            self::PAGAMENTO_DINHEIRO => self::PAGAMENTO_DINHEIRO,
            self::PAGAMENTO_PIX      => self::PAGAMENTO_PIX,
            self::PAGAMENTO_CARTAO   => self::PAGAMENTO_CARTAO,
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaFinanceira::class, 'categoria_financeira_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /**
     * Nome de quem "carrega" o lançamento, para agrupar no consolidado.
     */
    public function getResponsavelAttribute(): string
    {
        if ($this->escopo === self::ESCOPO_EMPRESA) {
            return 'Empresa';
        }

        return $this->user?->name ?? 'Sem usuário';
    }

    public function scopeEntradas(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_ENTRADA);
    }

    public function scopeSaidas(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_SAIDA);
    }

    public function scopeNoPeriodo(Builder $query, $inicio, $fim): Builder
    {
        return $query
            ->when($inicio, fn(Builder $q) => $q->whereDate('data_lancamento', '>=', $inicio))
            ->when($fim, fn(Builder $q) => $q->whereDate('data_lancamento', '<=', $fim));
    }

    protected static function booted(): void
    {
        // Registra a autoria e garante a coerência entre escopo e dono: lançamento
        // da empresa nunca guarda user_id, para não sujar os totais por pessoa.
        static::saving(function (self $lancamento) {
            if ($lancamento->escopo === self::ESCOPO_EMPRESA) {
                $lancamento->user_id = null;
            }

            if (! $lancamento->exists && ! $lancamento->registrado_por && Auth::check()) {
                $lancamento->registrado_por = Auth::id();
            }
        });
    }

    // --- Auditoria (RegistraLogFinanceiro) ---

    /**
     * Campos acompanhados pela trilha de auditoria, na ordem em que aparecem no log.
     */
    public function rotulosAuditoria(): array
    {
        return [
            'tipo'                    => 'Tipo',
            'escopo'                  => 'Fluxo',
            'user_id'                 => 'Usuário',
            'categoria_financeira_id' => 'Tipo de Entrada / Despesa',
            'descricao'               => 'Descrição',
            'valor'                   => 'Valor',
            'data_lancamento'         => 'Data',
            'forma_pagamento'         => 'Forma de Pagamento',
            'anexos'                  => 'Comprovantes',
        ];
    }

    public function descricaoAuditoria(): string
    {
        $tipo  = self::tipos()[$this->tipo] ?? $this->tipo;
        $valor = Number::currency((float) $this->valor, 'BRL', 'pt_BR');

        return "{$tipo} de {$valor} — {$this->descricao} ({$this->responsavel})";
    }

    protected function formatarValorAuditoria(string $campo, $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return match ($campo) {
            'tipo'   => self::tipos()[$valor] ?? (string) $valor,
            'escopo' => self::escopos()[$valor] ?? (string) $valor,
            'valor'  => Number::currency((float) $valor, 'BRL', 'pt_BR'),
            'data_lancamento' => Carbon::parse($valor)->format('d/m/Y'),
            'user_id' => User::find($valor)?->name ?? "Usuário #{$valor}",
            'categoria_financeira_id' => CategoriaFinanceira::find($valor)?->nome ?? "Tipo #{$valor}",
            'anexos' => is_array($valor) ? count($valor) . ' arquivo(s)' : null,
            default  => (string) $valor,
        };
    }
}
