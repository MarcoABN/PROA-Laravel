<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginCpf extends Component
{
    public $cpf;

    // app\Livewire\Auth\LoginCpf.php

    // app\Livewire\Auth\LoginCpf.php

    // app/Livewire/Auth/LoginCpf.php
    public function login()
    {
        $this->validate(['cpf' => 'required']);
        $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf);

        // CORREÇÃO: Busca na tabela 'users' em vez de 'clientes'
        $user = \App\Models\User::where('cpf', $cpfLimpo)->first();

        if ($user) {
            \Illuminate\Support\Facades\Auth::login($user);
            return redirect()->to('/dashboard');
        }

        session()->flash('error', 'CPF não encontrado.');
    }

    public function render()
    {
        return view('livewire.auth.login-cpf')
            ->layout('components.layouts.app'); // <--- Caminho explícito para a pasta components
    }
}