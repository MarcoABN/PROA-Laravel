<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;

class LoginCpf extends Component
{
    public $cpf;

    public function login()
    {
        try {
            $this->validate(['cpf' => 'required']);

            // Limpa o CPF mantendo apenas números
            $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf);

            // Busca o cliente
            $cliente = Cliente::where('cpfcnpj', $cpfLimpo)->first();

            if ($cliente) {
                // Loga usando o guard 'cliente'
                Auth::guard('cliente')->login($cliente);
                
                session()->regenerate();

                // Redireciona para a rota NOMEADA do cliente
                return redirect()->route('cliente.dashboard');
            }

            $this->addError('cpf', 'CPF não encontrado.');

        } catch (\Throwable $e) {
            // Em produção remova o dd() e use Log::error($e->getMessage());
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
            ->layout('components.layouts.app');
    }
}