<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use App\Models\Cliente; // Importe o model corretamente
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
                // CORREÇÃO PRINCIPAL AQUI:
                // 1. Usamos o guard 'cliente' especificamente
                // 2. Usamos o método login() passando o objeto do cliente
                Auth::guard('cliente')->login($cliente);
                
                // Recomendado por segurança: regenerar a sessão
                session()->regenerate();

                return redirect()->to('/dashboard'); 
                // Dica: Se tiver dashboard diferente para cliente, mude para ex: '/painel-cliente'
            }

            $this->addError('cpf', 'CPF não encontrado.'); // Jeito Livewire de mostrar erro no campo
            
        } catch (\Throwable $e) {
            // Para produção, use Log::error() em vez de dd()
            // \Illuminate\Support\Facades\Log::error($e->getMessage());
            
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