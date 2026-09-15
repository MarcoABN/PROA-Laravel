<?php

namespace App\Services\Agendamento;

use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Models\Prestador;
use App\Models\SolicitacaoAgendamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Encaixa cada solicitação num agendamento do procurador escolhido.
 *
 * Ordem de preferência: agendamento aberto que já tem o mesmo CPF → primeiro agendamento
 * aberto com vaga → um novo agendamento, se o procurador ainda tiver cota na capitania/mês.
 * Agendamentos que ficam vazios são descartados, liberando a cota.
 */
class AlocaSolicitacao
{
    private const ABERTOS = [AgendamentoMarinha::STATUS_PENDENTE, AgendamentoMarinha::STATUS_FALHOU];

    /**
     * @throws SemVagaException
     */
    public static function salvar(array $dados, ?SolicitacaoAgendamento $solicitacao = null): SolicitacaoAgendamento
    {
        // Data sugerida e período não são da solicitação: valem para o grupo procurador/capitania/mês.
        $informouPreferencias = array_key_exists('data_sugerida', $dados) || array_key_exists('periodo', $dados);
        $dataSugerida = filled($dados['data_sugerida'] ?? null) ? Carbon::parse($dados['data_sugerida'])->toDateString() : null;
        $periodo = in_array($dados['periodo'] ?? null, array_keys(AgendamentoMarinha::periodos()), true) ? $dados['periodo'] : null;
        unset($dados['data_sugerida'], $dados['periodo']);

        return DB::transaction(function () use ($dados, $solicitacao, $informouPreferencias, $dataSugerida, $periodo) {
            $solicitacao ??= new SolicitacaoAgendamento();
            $agendamentoAnterior = $solicitacao->agendamento_marinha_id;

            $solicitacao->fill($dados);
            $solicitacao->competencia = static::competencia($solicitacao->competencia);
            $solicitacao->cliente_cpf = SolicitacaoAgendamento::somenteDigitos($solicitacao->cliente_cpf);

            if ($dataSugerida && Carbon::parse($dataSugerida)->format('Y-m') !== Carbon::parse($solicitacao->competencia)->format('Y-m')) {
                throw new InvalidArgumentException('A data sugerida precisa estar dentro do mês do atendimento.');
            }

            if (!$solicitacao->exists || $solicitacao->isDirty(['prestador_id', 'capitania_id', 'competencia', 'cliente_cpf'])) {
                $solicitacao->agendamento_marinha_id = static::escolherAgendamento($solicitacao)->id;
            }

            $solicitacao->save();

            if ($agendamentoAnterior && $agendamentoAnterior !== $solicitacao->agendamento_marinha_id) {
                static::descartarSeVazio($agendamentoAnterior);
            }

            if ($informouPreferencias) {
                static::aplicarPreferencias($solicitacao, $dataSugerida, $periodo);
            }

            return $solicitacao;
        });
    }

    /**
     * Cadastro em lote de um mês: ['competencia', 'procuradores' => [['prestador_id', 'capitania_id', 'clientes' => [...]]]].
     * Cada item de procurador traz a sua capitania, então um lote pode misturar capitanias.
     * Tudo ou nada: se um procurador estourar a cota, nenhum serviço do lote é gravado.
     *
     * @return string o mês cadastrado, no formato Y-m
     *
     * @throws SemVagaException|InvalidArgumentException
     */
    public static function cadastrarLote(array $dados): string
    {
        $competencia = static::competencia($dados['competencia']);
        $itens = [];

        foreach ($dados['procuradores'] ?? [] as $procurador) {
            // Serviços do mesmo CPF entram em sequência, para caberem juntos no mesmo agendamento
            // (sem isso, outros clientes digitados no meio podem lotar o agendamento antes).
            $clientesPorCpf = collect($procurador['clientes'] ?? [])
                ->groupBy(fn(array $cliente) => SolicitacaoAgendamento::somenteDigitos($cliente['cliente_cpf'] ?? ''))
                ->sortByDesc(fn($servicos) => $servicos->count())
                ->flatten(1);

            foreach ($clientesPorCpf as $cliente) {
                $itens[] = $cliente + [
                    'prestador_id' => $procurador['prestador_id'],
                    'capitania_id' => $procurador['capitania_id'],
                    'competencia' => $competencia,
                    'data_sugerida' => $procurador['data_sugerida'] ?? null,
                    'periodo' => $procurador['periodo'] ?? null,
                ];
            }
        }

        if ($itens === []) {
            throw new InvalidArgumentException('Informe ao menos um cliente.');
        }

        $grus = array_map(fn(array $item) => SolicitacaoAgendamento::somenteDigitos($item['gru'] ?? ''), $itens);
        $repetidas = array_keys(array_filter(array_count_values($grus), fn(int $vezes) => $vezes > 1));

        if ($repetidas !== []) {
            throw new InvalidArgumentException('GRU informada mais de uma vez: ' . implode(', ', $repetidas) . '.');
        }

        DB::transaction(function () use ($itens) {
            foreach ($itens as $item) {
                static::salvar($item);
            }
        });

        return Carbon::parse($competencia)->format('Y-m');
    }

    public static function remover(SolicitacaoAgendamento $solicitacao): void
    {
        DB::transaction(function () use ($solicitacao) {
            $agendamentoId = $solicitacao->agendamento_marinha_id;
            $solicitacao->delete();
            static::descartarSeVazio($agendamentoId);
        });
    }

    /**
     * Quantos serviços ainda cabem para o procurador na capitania/mês (ignorando a própria solicitação ao editar).
     */
    public static function vagasLivres(int $prestadorId, int $capitaniaId, mixed $competencia, ?SolicitacaoAgendamento $ignorar = null): int
    {
        $capitania = Capitania::find($capitaniaId);

        if (!$capitania) {
            return 0;
        }

        [$vagas, $limite] = static::regras($capitania);

        $ativos = static::agendamentosAtivos($prestadorId, $capitaniaId, static::competencia($competencia));
        $ocupadas = static::ocupadas($ativos->pluck('id'), $ignorar);

        $livres = $ativos
            ->filter(fn(AgendamentoMarinha $a) => in_array($a->status, self::ABERTOS))
            ->sum(fn(AgendamentoMarinha $a) => max(0, $vagas - ($ocupadas[$a->id] ?? 0)));

        return $livres + max(0, $limite - $ativos->count()) * $vagas;
    }

    private static function escolherAgendamento(SolicitacaoAgendamento $solicitacao): AgendamentoMarinha
    {
        $capitania = Capitania::findOrFail($solicitacao->capitania_id);
        [$vagas, $limite] = static::regras($capitania);

        $ativos = static::agendamentosAtivos(
            $solicitacao->prestador_id,
            $solicitacao->capitania_id,
            $solicitacao->competencia,
            travar: true,
        );
        $ocupadas = static::ocupadas($ativos->pluck('id'), $solicitacao);

        $abertos = $ativos->filter(fn(AgendamentoMarinha $a) => in_array($a->status, self::ABERTOS)
            && ($ocupadas[$a->id] ?? 0) < $vagas);

        $comMesmoCpf = SolicitacaoAgendamento::query()
            ->whereIn('agendamento_marinha_id', $abertos->pluck('id'))
            ->where('cliente_cpf', $solicitacao->cliente_cpf)
            ->when($solicitacao->exists, fn($q) => $q->whereKeyNot($solicitacao->id))
            ->value('agendamento_marinha_id');

        if ($comMesmoCpf) {
            return $abertos->firstWhere('id', $comMesmoCpf);
        }

        if ($abertos->isNotEmpty()) {
            return $abertos->first();
        }

        if ($ativos->count() >= $limite) {
            $procurador = Prestador::find($solicitacao->prestador_id)?->nome ?? 'O procurador';
            $mes = Carbon::parse($solicitacao->competencia)->format('m/Y');

            throw new SemVagaException("{$procurador} não tem mais vagas na {$capitania->sigla} em {$mes}.");
        }

        // Ordem conta também os cancelados, para o número nunca se repetir no mês.
        $ultimaOrdem = AgendamentoMarinha::query()
            ->where('prestador_id', $solicitacao->prestador_id)
            ->where('capitania_id', $solicitacao->capitania_id)
            ->whereDate('competencia', $solicitacao->competencia)
            ->max('ordem');

        // O novo agendamento herda a preferência de data/período dos irmãos do grupo.
        $irmao = $ativos->first();

        return AgendamentoMarinha::create([
            'prestador_id' => $solicitacao->prestador_id,
            'capitania_id' => $solicitacao->capitania_id,
            'competencia' => $solicitacao->competencia,
            'ordem' => (int) $ultimaOrdem + 1,
            'status' => AgendamentoMarinha::STATUS_PENDENTE,
            'data_sugerida' => $irmao?->data_sugerida,
            'periodo' => $irmao?->periodo,
        ]);
    }

    /**
     * Grava a preferência em todos os agendamentos ainda por marcar do procurador na capitania/mês.
     */
    private static function aplicarPreferencias(SolicitacaoAgendamento $solicitacao, ?string $dataSugerida, ?string $periodo): void
    {
        AgendamentoMarinha::query()
            ->where('prestador_id', $solicitacao->prestador_id)
            ->where('capitania_id', $solicitacao->capitania_id)
            ->whereDate('competencia', Carbon::parse($solicitacao->competencia)->toDateString())
            ->whereIn('status', self::ABERTOS)
            ->update(['data_sugerida' => $dataSugerida, 'periodo' => $periodo]);
    }

    private static function descartarSeVazio(int $agendamentoId): void
    {
        AgendamentoMarinha::whereKey($agendamentoId)
            ->where('status', '!=', AgendamentoMarinha::STATUS_AGENDADO)
            ->whereDoesntHave('solicitacoes')
            ->delete();
    }

    /** @return Collection<int, AgendamentoMarinha> */
    private static function agendamentosAtivos(int $prestadorId, int $capitaniaId, string $competencia, bool $travar = false): Collection
    {
        return AgendamentoMarinha::query()
            ->where('prestador_id', $prestadorId)
            ->where('capitania_id', $capitaniaId)
            ->whereDate('competencia', $competencia)
            ->where('status', '!=', AgendamentoMarinha::STATUS_CANCELADO)
            ->orderBy('ordem')
            ->when($travar, fn($q) => $q->lockForUpdate())
            ->get();
    }

    /** @return Collection<int, int> quantidade de solicitações por agendamento */
    private static function ocupadas(Collection $agendamentoIds, ?SolicitacaoAgendamento $ignorar): Collection
    {
        return SolicitacaoAgendamento::query()
            ->whereIn('agendamento_marinha_id', $agendamentoIds)
            ->when($ignorar?->exists, fn($q) => $q->whereKeyNot($ignorar->id))
            ->selectRaw('agendamento_marinha_id, COUNT(*) as total')
            ->groupBy('agendamento_marinha_id')
            ->pluck('total', 'agendamento_marinha_id');
    }

    /** @return array{0: int, 1: int} [vagas por agendamento, agendamentos por mês] */
    private static function regras(Capitania $capitania): array
    {
        return [
            max(1, (int) $capitania->sisap_vagas_por_agendamento),
            max(0, (int) $capitania->sisap_agendamentos_por_mes),
        ];
    }

    private static function competencia(mixed $valor): string
    {
        return Carbon::parse($valor)->startOfMonth()->toDateString();
    }
}
