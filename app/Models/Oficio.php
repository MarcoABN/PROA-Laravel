<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Oficio extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'escola_nautica_id', 
        'capitania_id', 
        'instrutor_id',
        'sequencial', 
        'ano', 
        'assunto', 
        'data_aula', 
        'periodo_aula', 
        'local_aula', 
        'cidade_aula'
    ];

    protected $casts = [
        'data_aula' => 'date',
    ];

    protected function numeroOficio(): Attribute
    {
        return Attribute::make(
            get: fn () => str_pad($this->sequencial, 3, '0', STR_PAD_LEFT) . '/' . $this->ano,
        );
    }

    // --- CORREÇÃO AQUI ---
    protected static function booted()
    {
        static::creating(function ($oficio) {
            $anoAtual = date('Y');
            
            // Adicionamos ->withTrashed() para que ele conte também os ofícios deletados
            // Assim, se o ofício 1 foi deletado, o sistema gerará o 2, e não tentará recriar o 1.
            $ultimo = static::withTrashed()
                ->where('ano', $anoAtual)
                ->max('sequencial') ?? 0;
            
            $oficio->ano = $anoAtual;
            $oficio->sequencial = $ultimo + 1;
        });
    }
    // ---------------------

    public function escola(): BelongsTo 
    { 
        return $this->belongsTo(EscolaNautica::class, 'escola_nautica_id'); 
    }

    public function capitania(): BelongsTo 
    { 
        return $this->belongsTo(Capitania::class, 'capitania_id'); 
    }

    public function instrutor(): BelongsTo 
    { 
        return $this->belongsTo(Prestador::class, 'instrutor_id'); 
    }
    
    public function itens(): HasMany 
    { 
        return $this->hasMany(OficioCliente::class, 'oficio_id'); 
    }
}