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

        // --- CORREÇÃO AQUI ---
        // Antes você buscava em Cliente. Agora TEM que ser em User.
        // O Laravel só aceita logar o objeto que está no auth.php
        $user = \App\Models\User::where('cpf', $cpfLimpo)->first();

        if ($user) {
            // Agora o Auth::login recebe um User, que bate com a configuração do auth.php
            \Illuminate\Support\Facades\Auth::login($user);
            return redirect()->to('/dashboard'); // ou para a rota do simulado
        }

        session()->flash('error', 'CPF não encontrado ou não cadastrado como Usuário.');
    }

    public function render()
    {
        return view('livewire.auth.login-cpf')
            ->layout('components.layouts.app'); // <--- Caminho explícito para a pasta components
    }
}