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

    public function login()
    {

        dd("Chegou no PHP!");
        $this->validate(['cpf' => 'required']);
        $cpfLimpo = preg_replace('/[^0-9]/', '', $this->cpf);

        // Busca o cliente na tabela correta
        $cliente = \App\Models\Cliente::where('cpfcnpj', $cpfLimpo)->first();

        if ($cliente) {
            // Agora o Laravel aceitará o objeto $cliente porque ele é Authenticatable
            \Illuminate\Support\Facades\Auth::login($cliente);
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