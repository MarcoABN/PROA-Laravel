<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgendamentoMarinha;
use App\Models\Prestador;
use App\Models\SolicitacaoAgendamento;
use App\Services\Agendamento\EscolhaDeHorario;
use App\Services\Agendamento\RegistraResultadoAgendamento;
use App\Support\ExtensaoChrome;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SisapAgendamentoController extends Controller
{
    /**
     * Agendamentos deste procurador que ainda precisam ser marcados no SISAP (inclui os que falharam).
     */
    public function index(Request $request): JsonResponse
    {
        $prestador = $this->prestador($request);

        $agendamentos = AgendamentoMarinha::with(['capitania', 'solicitacoes.servico'])
            ->where('prestador_id', $prestador->id)
            ->whereIn('status', [AgendamentoMarinha::STATUS_PENDENTE, AgendamentoMarinha::STATUS_FALHOU])
            ->orderBy('competencia')
            ->orderBy('ordem')
            ->get();

        return response()->json([
            'procurador' => [
                'nome' => $prestador->nome,
                'cpf' => SolicitacaoAgendamento::somenteDigitos($prestador->cpfcnpj),
            ],
            'extensao' => $this->extensao($request),
            'agendamentos' => $agendamentos->map(fn(AgendamentoMarinha $a) => $this->formatar($a))->values(),
        ]);
    }

    /**
     * A extensão manda os horários livres que o SISAP mostrou; o PROA aplica a regra de prioridade.
     */
    public function escolherHorario(Request $request, AgendamentoMarinha $agendamento): JsonResponse
    {
        $this->autorizar($request, $agendamento);

        $request->validate([
            'horarios' => ['required', 'array', 'min:1'],
            'horarios.*.data' => ['required', 'date_format:Y-m-d'],
            'horarios.*.hora' => ['required', 'date_format:H:i'],
            'horarios.*.turno' => ['nullable', 'in:manha,tarde'],
        ]);

        // Entrada crua (não a validada) para devolver intactas as chaves extras que a extensão mandar.
        $horarios = $request->input('horarios');
        $referencia = $agendamento->referenciaDeHorario();

        // Sem referência, a data sempre é escolhida com espaço para um segundo agendamento no mesmo período.
        $plano = $referencia
            ? null
            : EscolhaDeHorario::planejarPar($horarios, $agendamento->data_sugerida, $agendamento->periodo, $agendamento->segunda_data_sugerida);

        return response()->json([
            'horario' => EscolhaDeHorario::escolher(
                $horarios, $referencia, $agendamento->data_sugerida, $agendamento->periodo, $agendamento->segunda_data_sugerida,
            ),
            'referencia' => $referencia?->format('Y-m-d H:i'),
            'plano' => $plano,
            'preferencia' => $this->preferencia($agendamento),
        ]);
    }

    public function resultado(Request $request, AgendamentoMarinha $agendamento): JsonResponse
    {
        $this->autorizar($request, $agendamento);

        $dados = $request->validate([
            'numero' => ['required', 'string', 'max:50'],
            'chave' => ['required', 'string', 'max:20'],
            'data_hora' => ['required', 'date_format:Y-m-d H:i'],
            'sisap_id' => ['nullable', 'string', 'max:50'],
            'comprovante_link' => ['nullable', 'string', 'max:2000'],
        ]);

        RegistraResultadoAgendamento::registrar(
            $agendamento,
            numero: $dados['numero'],
            chave: $dados['chave'],
            dataHora: Carbon::createFromFormat('Y-m-d H:i', $dados['data_hora']),
            sisapId: $dados['sisap_id'] ?? null,
            comprovanteLink: $dados['comprovante_link'] ?? null,
        );

        return response()->json(['ok' => true]);
    }

    public function falha(Request $request, AgendamentoMarinha $agendamento): JsonResponse
    {
        $this->autorizar($request, $agendamento);

        $dados = $request->validate([
            'mensagem' => ['required', 'string', 'max:2000'],
        ]);

        // Um agendamento já confirmado não pode voltar para "falhou" por um relato atrasado.
        if ($agendamento->status !== AgendamentoMarinha::STATUS_AGENDADO) {
            $agendamento->update([
                'status' => AgendamentoMarinha::STATUS_FALHOU,
                'erro' => $dados['mensagem'],
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * O SISAP recusou um cliente (GRU já utilizada, documento inválido...): a extensão o tira da tela
     * e segue com os demais. Ele não é mais enviado até ser corrigido no cadastro.
     */
    public function descartar(Request $request, AgendamentoMarinha $agendamento): JsonResponse
    {
        $this->autorizar($request, $agendamento);

        $dados = $request->validate([
            'solicitacao_ids' => ['required', 'array', 'min:1'],
            'solicitacao_ids.*' => ['integer'],
            'motivo' => ['required', 'string', 'max:2000'],
        ]);

        // Depois de marcado no SISAP, a lista de clientes que foram não muda mais.
        if ($agendamento->status !== AgendamentoMarinha::STATUS_AGENDADO) {
            $agendamento->solicitacoes()
                ->whereKey($dados['solicitacao_ids'])
                ->get()
                ->each(fn(SolicitacaoAgendamento $s) => $s->descartar($dados['motivo']));
        }

        return response()->json(['ok' => true]);
    }

    private function formatar(AgendamentoMarinha $agendamento): array
    {
        $interessados = $agendamento->solicitacoes
            ->reject(fn(SolicitacaoAgendamento $s) => $s->descartada())
            ->sortBy('id')
            ->groupBy('cliente_documento')
            ->map(fn($solicitacoes, $documento) => [
                'documento' => (string) $documento,
                'tipo_documento' => SolicitacaoAgendamento::tipoDocumento((string) $documento),
                // Extensões até a 0.2.2 só conhecem "cpf".
                'cpf' => (string) $documento,
                'nome' => $solicitacoes->first()->cliente_nome,
                'servicos' => $solicitacoes->map(fn(SolicitacaoAgendamento $s) => [
                    'solicitacao_id' => $s->id,
                    'gru' => $s->gru,
                    'sigla' => $s->servico?->sigla,
                    'descricao_sisap' => $s->servico?->descricao_sisap,
                ])->values(),
            ])
            ->values();

        return [
            'id' => $agendamento->id,
            'status' => $agendamento->status,
            'erro' => $agendamento->erro,
            'competencia' => $agendamento->competencia->format('Y-m'),
            'ordem' => $agendamento->ordem,
            'capitania' => [
                'nome' => $agendamento->capitania->nome,
                'sigla' => $agendamento->capitania->sigla,
                'nidom' => $agendamento->capitania->sisap_nidom,
                'vagas_por_agendamento' => $agendamento->vagasTotais(),
            ],
            'referencia_horario' => $agendamento->referenciaDeHorario()?->format('Y-m-d H:i'),
            'preferencia' => $this->preferencia($agendamento),
            'interessados' => $interessados,
            // Recusados pelo SISAP numa tentativa anterior: o painel mostra, mas não envia.
            'descartados' => $agendamento->solicitacoes
                ->filter(fn(SolicitacaoAgendamento $s) => $s->descartada())
                ->map(fn(SolicitacaoAgendamento $s) => [
                    'nome' => $s->cliente_nome,
                    'documento' => $s->cliente_documento,
                    'gru' => $s->gru,
                    'motivo' => $s->motivo_descarte,
                ])
                ->values(),
        ];
    }

    private function preferencia(AgendamentoMarinha $agendamento): array
    {
        return [
            'data' => $agendamento->data_sugerida?->toDateString(),
            'segunda_data' => $agendamento->segunda_data_sugerida?->toDateString(),
            'periodo' => $agendamento->periodo,
        ];
    }

    /**
     * Quem é o token e, se já identificado, o procurador. Usado pelas opções da extensão para testar.
     */
    public function eu(Request $request): JsonResponse
    {
        $usuario = $request->attributes->get('usuario');
        $prestador = $request->attributes->get('prestador');

        return response()->json([
            'tipo' => $usuario ? 'usuario' : 'procurador',
            'nome' => $usuario?->name ?? $prestador?->nome,
            'procurador' => $prestador ? [
                'nome' => $prestador->nome,
                'cpf' => SolicitacaoAgendamento::somenteDigitos($prestador->cpfcnpj),
            ] : null,
            'aviso' => $request->attributes->get('erroProcurador'),
            'extensao' => $this->extensao($request),
        ]);
    }

    /** Versão da extensão que chamou x versão publicada no servidor. */
    private function extensao(Request $request): array
    {
        $usada = $request->header('X-Extensao-Versao');

        return [
            'versao_usada' => $usada,
            'versao_atual' => ExtensaoChrome::versaoAtual(),
            'desatualizada' => ExtensaoChrome::desatualizada($usada),
        ];
    }

    private function prestador(Request $request): Prestador
    {
        $prestador = $request->attributes->get('prestador');

        if (!$prestador) {
            abort(response()->json([
                'message' => $request->attributes->get('erroProcurador') ?? 'Procurador não identificado.',
            ], 422));
        }

        return $prestador;
    }

    private function autorizar(Request $request, AgendamentoMarinha $agendamento): void
    {
        // 404 em vez de 403: um procurador nem fica sabendo que o agendamento de outro existe.
        abort_unless($agendamento->prestador_id === $this->prestador($request)->id, 404);
    }
}
