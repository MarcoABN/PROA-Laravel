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
    public $ordemAlternativas = []; // Mapeia id_da_questao => ['c', 'a', 'e', 'b', 'd']
    public $modalidade; 
    public $tempoRestante = 7200; // 120 min (2 horas)
    public $finalizado = false;
    public $resultado = null;

    public function mount($modalidade = 'aprendizado')
    {
        $this->modalidade = $modalidade;
        $this->tempoRestante = ($modalidade === 'real') ? 7200 : null;

        $this->questoes = Questao::where('ativo', true)
            ->inRandomOrder()
            ->limit(40)
            ->get();

        foreach ($this->questoes as $q) {
            $this->respostasUsuario[$q->id] = null;

            // Identifica quais alternativas estão preenchidas no banco (a até e)
            $letrasDisponiveis = [];
            foreach (['a', 'b', 'c', 'd', 'e'] as $l) {
                $coluna = "alternativa_" . $l;
                if (!empty($q->$coluna)) {
                    $letrasDisponiveis[] = $l;
                }
            }

            // Embaralha a ordem das chaves técnicas
            shuffle($letrasDisponiveis);
            $this->ordemAlternativas[$q->id] = $letrasDisponiveis;
        }
    }

    public function responder($questaoId, $letraOriginal)
    {
        if ($this->finalizado) return;
        
        // Armazena a letra original da coluna (a, b, c, d ou e) para bater com 'resposta_correta'
        $this->respostasUsuario[$questaoId] = $letraOriginal;
    }

    public function finalizar()
    {
        if ($this->finalizado) return;

        $totalQuestões = count($this->questoes);
        $acertos = 0;

        foreach ($this->questoes as $q) {
            // Comparação direta entre a resposta salva e o campo do banco
            if (($this->respostasUsuario[$q->id] ?? null) === $q->resposta_correta) {
                $acertos++;
            }
        }

        $erros = $totalQuestões - $acertos;
        $porcentagem = ($totalQuestões > 0) ? ($acertos / $totalQuestões) * 100 : 0;
        $aprovado = $porcentagem >= 50;

        try {
            SimuladoResultado::create([
                'cliente_id' => Auth::guard('cliente')->id(),
                'modalidade' => $this->modalidade,
                'acertos' => $acertos,
                'erros' => $erros,
                'total' => $totalQuestões,
                'porcentagem' => $porcentagem,
                'aprovado' => $aprovado
            ]);
        } catch (\Exception $e) {
            // Silencioso para não travar a experiência do aluno
        }

        $this->resultado = [
            'acertos' => $acertos,
            'erros' => $erros,
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
        if ($this->modalidade === 'real' && $this->tempoRestante > 0) {
            $this->tempoRestante--;

            if ($this->tempoRestante === 0) {
                $this->finalizar();
            }
        }
    }
}