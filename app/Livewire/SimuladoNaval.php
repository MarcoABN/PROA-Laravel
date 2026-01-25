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
    public $tempoRestante = 7200; // 2 horas
    public $finalizado = false;
    public $resultado = null;

    public function mount($modalidade = 'aprendizado')
    {
        $this->modalidade = $modalidade;
        $this->tempoRestante = ($modalidade === 'real') ? 7200 : null; // 60 min apenas se real

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

        $totalQuestões = count($this->questoes);
        $acertos = 0;

        foreach ($this->questoes as $q) {
            if (($this->respostasUsuario[$q->id] ?? null) === $q->resposta_correta) {
                $acertos++;
            }
        }

        // Cálculo simples dos erros
        $erros = $totalQuestões - $acertos;

        $porcentagem = ($totalQuestões > 0) ? ($acertos / $totalQuestões) * 100 : 0;
        $aprovado = $porcentagem >= 50;

        // Salva no banco
        try {
            SimuladoResultado::create([
                'cliente_id' => Auth::guard('cliente')->id(), // <--- Pega o ID do Cliente Logado
                'modalidade' => $this->modalidade,
                'acertos' => $acertos,
                'erros' => $erros, // <--- Salvando erros
                'total' => $totalQuestões,
                'porcentagem' => $porcentagem,
                'aprovado' => $aprovado
            ]);
        } catch (\Exception $e) {
            // Em produção use Log::error($e);
            // Isso evita que o aluno trave na tela de resultado se o banco falhar
        }

        $this->resultado = [
            'acertos' => $acertos,
            'erros' => $erros, // Disponível para a view
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