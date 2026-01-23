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
        try {
            $this->validate(['cpf' => 'required']);
            $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf);

            // Teste de conexão simples
            $cliente = \App\Models\Cliente::where('cpfcnpj', $cpfLimpo)->first();

            if ($cliente) {
                \Illuminate\Support\Facades\Auth::login($cliente);
                return redirect()->to('/dashboard');
            }

            session()->flash('error', 'CPF não encontrado.');
        } catch (\Throwable $e) {
            // Isso vai parar a execução e mostrar o erro real na tela
            dd([
                'Mensagem' => $e->getMessage(),
                'Arquivo' => $e->getFile(),
                'Linha' => $e->getLine()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.auth.login-cpf')
            ->layout('components.layouts.app'); // <--- Caminho explícito para a pasta components
    }
}