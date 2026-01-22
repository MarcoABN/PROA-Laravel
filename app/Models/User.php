<?php

namespace App\Models;

// 1. ADICIONE ESSES DOIS IMPORTS
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 2. ADICIONE "implements FilamentUser" AQUI
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    // 3. ADICIONE ESTA FUNÇÃO NO FINAL DA CLASSE
    public function canAccessPanel(Panel $panel): bool
    {
        // Retorne true para permitir que este usuário acesse o painel.
        // Para maior segurança no futuro, você pode colocar:
        // return str_ends_with($this->email, '@campeaonautica.com.br');
        
        return true; 
    }
}