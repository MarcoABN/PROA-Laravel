<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon; // <--- ADICIONE ESTA IMPORTAÇÃO

class Proposta extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data_proposta' => 'date', // O Laravel converte para Carbon, mas a IDE as vezes não vê
        'itens_servico' => 'array',
        'parcelas' => 'array',
        'valor_desconto' => 'decimal:2',
    ];

    // Lógica para gerar o número formatado: ddmmyyyy-001
    public function getNumeroFormatadoAttribute()
    {
        // CORREÇÃO AQUI:
        // Usamos Carbon::parse() para garantir para a IDE e para o PHP 
        // que estamos lidando com um objeto de data válido.
        return Carbon::parse($this->data_proposta)->format('dmY') . '-' . str_pad($this->sequencial_diario, 3, '0', STR_PAD_LEFT);
    }

    public function escola(): BelongsTo { return $this->belongsTo(EscolaNautica::class, 'escola_nautica_id'); }
    public function cliente(): BelongsTo { return $this->belongsTo(Cliente::class); }
    public function embarcacao(): BelongsTo { return $this->belongsTo(Embarcacao::class); }
}