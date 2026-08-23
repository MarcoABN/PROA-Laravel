<?php

namespace App\Models;

// 1. ADICIONE ESSES DOIS IMPORTS
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

// 2. ADICIONE "implements FilamentUser" AQUI
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * Conta do administrador do sistema. Já era tratada de forma especial
     * (fica fora da lista de Usuários); a constante evita o e-mail solto
     * espalhado pelo código.
     */
    const EMAIL_ADMINISTRADOR = 'marcoanunes23@gmail.com';

    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
        'pode_acessar_financeiro',
        'pode_gerenciar_usuarios',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'pode_acessar_financeiro' => 'boolean',
        'pode_gerenciar_usuarios' => 'boolean',
    ];

    // 3. ADICIONE ESTA FUNÇÃO NO FINAL DA CLASSE
    public function canAccessPanel(Panel $panel): bool
    {
        // Retorne true para permitir que este usuário acesse o painel.
        // Para maior segurança no futuro, você pode colocar:
        // return str_ends_with($this->email, '@campeaonautica.com.br');
        
        return true; 
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'user_id');
    }

    public function lancamentosFinanceiros(): HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'user_id');
    }

    public function ehAdministrador(): bool
    {
        return $this->email === self::EMAIL_ADMINISTRADOR;
    }

    /**
     * Abre a tela de Usuários do Sistema. O administrador entra sempre, mesmo
     * que a coluna esteja desmarcada — é ele quem distribui os acessos.
     */
    public function podeGerenciarUsuarios(): bool
    {
        return $this->ehAdministrador() || (bool) $this->pode_gerenciar_usuarios;
    }

    /**
     * Marcar e desmarcar as permissões dos outros. Exclusivo do administrador:
     * se quem gerencia usuários também pudesse conceder, a permissão voltaria
     * a se espalhar sozinha, que é justamente o que se quer evitar.
     */
    public function podeConcederPermissoes(): bool
    {
        return $this->ehAdministrador();
    }

    /**
     * O administrador lança em nome dos outros, mas não tem fluxo próprio:
     * nenhum lançamento pode ficar no nome dele.
     */
    public function podeReceberLancamento(): bool
    {
        return ! $this->ehAdministrador();
    }

    /**
     * Usuários que podem ser donos de um lançamento financeiro.
     *
     * O e-mail é anulável nesta base, e "email != x" no SQL descarta as linhas
     * com NULL — daí o whereNull explícito, senão usuários sem e-mail sumiriam
     * da lista.
     */
    public function scopeRecebeLancamento(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('email')
                ->orWhere('email', '!=', self::EMAIL_ADMINISTRADOR);
        });
    }
}