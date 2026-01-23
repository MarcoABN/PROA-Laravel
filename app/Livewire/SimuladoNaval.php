<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Questao;
use App\Models\SimuladoResultado;
use Illuminate\Support\Facades\Auth;

class SimuladoNaval extends Component
{
    public $questoes = [];
    public $respostasUsuario = [];
    public $modalidade; // 'aprendizado' ou 'real'
    public $tempoRestante = 3600; // 60 min
    public $finalizado = false;
    public $resultado = null;

    public function mount($modalidade = 'aprendizado')
    {
        $this->modalidade = $modalidade;
        $this->tempoRestante = ($modalidade === 'real') ? 3600 : null; // 60 min apenas se real

        $this->questoes = Questao::where('ativo', true)
            ->inRandomOrder()
            ->limit(40)
            ->get();

        foreach ($this->questoes as $q) {
            $this->respostasUsuario[$q->id] = null;
        }
    }

    public function responder($questaoId, $letra)
    {
        if ($this->finalizado)
            return;
        $this->respostasUsuario[$questaoId] = $letra;
    }

    public function finalizar()
    {
        if ($this->finalizado)
            return;

        $acertos = 0;
        foreach ($this->questoes as $q) {
            if (($this->respostasUsuario[$q->id] ?? null) === $q->resposta_correta) {
                $acertos++;
            }
        }

        $porcentagem = ($acertos / 40) * 100;
        $aprovado = $porcentagem >= 50; // Sua regra de 50%

        if ($this->modalidade === 'real') {
            SimuladoResultado::create([
                'user_id' => Auth::id(),
                'modalidade' => 'real',
                'acertos' => $acertos,
                'total' => 40,
                'porcentagem' => $porcentagem,
                'aprovado' => $aprovado
            ]);
        }

        $this->resultado = [
            'acertos' => $acertos,
            'porcentagem' => $porcentagem,
            'aprovado' => $aprovado
        ];
        $this->finalizado = true;
    }

    public function render()
    {
        return view('livewire.simulado-naval');
    }

    public function decrementarTempo()
    {
        // Só executa se for modo real e ainda houver tempo
        if ($this->modalidade === 'real' && $this->tempoRestante > 0) {
            $this->tempoRestante--;

            // Se o tempo acabar, finaliza o simulado automaticamente
            if ($this->tempoRestante === 0) {
                $this->finalizar();
            }
        }
    }
}