<?php

namespace App\Services\Agendamento;

use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Models\Prestador;
use App\Models\SolicitacaoAgendamento;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cadastro de agendamentos de uma Capitania + Mês — a chave do cadastro, única e imutável.
 *
 * Dentro dele: procuradores (cada um com data sugerida e período) → 1º e 2º agendamento → clientes.
 * O formulário envia o estado completo e aqui ele é sincronizado com agendamentos_marinha e
 * agendamento_solicitacoes. Agendamentos já marcados no SISAP (status agendado) nunca são alterados.
 */
class CadastroDoMes
{
    /** O cadastro mostra o 1º e o 2º agendamento de cada procurador. */
    public const AGENDAMENTOS_POR_PROCURADOR = 2;

    public static function competencia(mixed $valor): string
    {
        return Carbon::parse($valor)->startOfMonth()->toDateString();
    }

    public static function existe(int $capitaniaId, mixed $competencia): bool
    {
        return static::consultaAtivos($capitaniaId, static::competencia($competencia))->exists();
    }

    /**
     * Capitanias que já têm cadastro no mês — não podem ser escolhidas num novo cadastro.
     *
     * @return array<int, int>
     */
    public static function capitaniasComCadastro(mixed $competencia): array
    {
        return AgendamentoMarinha::query()
            ->whereDate('competencia', static::competencia($competencia))
            ->where('status', '!=', AgendamentoMarinha::STATUS_CANCELADO)
            ->distinct()
            ->pluck('capitania_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    /**
     * Estado do formulário para editar o cadastro.
     */
    public static function carregar(int $capitaniaId, mixed $competencia): array
    {
        $competencia = static::competencia($competencia);

        $procuradores = static::consultaAtivos($capitaniaId, $competencia)
            ->with(['prestador', 'solicitacoes' => fn($q) => $q->orderBy('id')])
            ->orderBy('ordem')
            ->get()
            ->groupBy('prestador_id')
            ->map(function (Collection $agendamentos, $prestadorId) {
                $agendamentos = $agendamentos->values();
                $primeiro = $agendamentos->first();

                $bloco = [
                    'prestador_id' => (int) $prestadorId,
                    'data_sugerida' => $primeiro->data_sugerida?->toDateString(),
                    'periodo' => $primeiro->periodo,
                ];

                for ($ordem = 1; $ordem <= self::AGENDAMENTOS_POR_PROCURADOR; $ordem++) {
                    $agendamento = $agendamentos->get($ordem - 1);

                    $bloco["agendamento_{$ordem}"] = [
                        'id' => $agendamento?->id,
                        'clientes' => $agendamento
                            ? $agendamento->solicitacoes->map(fn(SolicitacaoAgendamento $s) => [
                                'id' => $s->id,
                                'cliente_cpf' => $s->cliente_cpf,
                                'cliente_nome' => $s->cliente_nome,
                                'gru' => $s->gru,
                                'sisap_servico_id' => $s->sisap_servico_id,
                            ])->all()
                            : [],
                    ];
                }

                return $bloco + ['nome' => $primeiro->prestador?->nome];
            })
            ->sortBy('nome')
            ->map(fn(array $bloco) => collect($bloco)->except('nome')->all())
            ->values()
            ->all();

        return [
            'competencia' => $competencia,
            'capitania_id' => $capitaniaId,
            'procuradores' => $procuradores,
        ];
    }

    /**
     * @param  array  $procuradores  blocos do formulário: prestador_id, data_sugerida, periodo,
     *                               agendamento_1/agendamento_2 => [id, clientes => [id, cliente_cpf, cliente_nome, gru, sisap_servico_id]]
     * @param  bool  $novo  criando o cadastro (a chave não pode existir) ou editando
     *
     * @throws InvalidArgumentException
     */
    public static function salvar(int $capitaniaId, mixed $competencia, array $procuradores, bool $novo): void
    {
        $competencia = static::competencia($competencia);
        $capitania = Capitania::findOrFail($capitaniaId);
        $procuradores = static::normalizar($procuradores);
        $rotulo = "{$capitania->sigla} " . Carbon::parse($competencia)->format('m/Y');

        static::validar($capitania, $competencia, $procuradores, $rotulo);

        try {
            static::gravar($capitania, $competencia, $procuradores, $novo, $rotulo);
        } catch (UniqueConstraintViolationException $e) {
            // Índice único mês + GRU: pega o que a validação não previu (ex.: duas GRUs trocadas entre clientes).
            throw new InvalidArgumentException('Não foi possível salvar: há GRU repetida neste mês. Confira as GRUs dos clientes.');
        }
    }

    /**
     * Exclui um agendamento e os clientes dele (as GRUs ficam livres) e renumera os demais agendamentos
     * do procurador na capitania/mês, para continuarem sendo 1º e 2º.
     *
     * @return int quantos clientes foram excluídos
     */
    public static function excluirAgendamento(AgendamentoMarinha $agendamento): int
    {
        return DB::transaction(function () use ($agendamento) {
            $clientes = $agendamento->solicitacoes()->delete();
            $agendamento->delete();

            AgendamentoMarinha::query()
                ->where('prestador_id', $agendamento->prestador_id)
                ->where('capitania_id', $agendamento->capitania_id)
                ->whereDate('competencia', $agendamento->competencia->toDateString())
                ->orderBy('ordem')
                ->get()
                ->values()
                ->each(function (AgendamentoMarinha $restante, int $posicao) {
                    if ($restante->ordem !== $posicao + 1) {
                        $restante->update(['ordem' => $posicao + 1]);
                    }
                });

            return $clientes;
        });
    }

    /**
     * Exclui um cliente de um agendamento ainda não marcado no SISAP (a GRU fica livre).
     * Se era o único cliente, o agendamento vazio também é excluído.
     *
     * @return bool se o agendamento também foi excluído
     *
     * @throws InvalidArgumentException
     */
    public static function excluirCliente(SolicitacaoAgendamento $solicitacao): bool
    {
        return DB::transaction(function () use ($solicitacao) {
            $agendamento = AgendamentoMarinha::whereKey($solicitacao->agendamento_marinha_id)->lockForUpdate()->first();

            if ($agendamento?->status === AgendamentoMarinha::STATUS_AGENDADO) {
                throw new InvalidArgumentException("O {$agendamento->ordem}º agendamento já foi marcado no SISAP (nº {$agendamento->numero}): "
                    . 'os clientes dele não podem ser excluídos um a um. Exclua o agendamento inteiro.');
            }

            if ($agendamento && $agendamento->solicitacoes()->whereKeyNot($solicitacao->id)->doesntExist()) {
                static::excluirAgendamento($agendamento);

                return true;
            }

            $solicitacao->delete();

            return false;
        });
    }

    private static function gravar(Capitania $capitania, string $competencia, array $procuradores, bool $novo, string $rotulo): void
    {
        DB::transaction(function () use ($capitania, $competencia, $procuradores, $novo, $rotulo) {
            // Trava a capitania: dois "Novo cadastro" simultâneos da mesma chave não passam juntos.
            Capitania::whereKey($capitania->id)->lockForUpdate()->first();

            if ($novo && static::existe($capitania->id, $competencia)) {
                throw new InvalidArgumentException("Já existe cadastro para {$rotulo}. Abra-o em \"Editar cadastro\".");
            }

            // Editar não recria a chave: se o cadastro deixou de existir, não há o que editar.
            if (!$novo && !static::existe($capitania->id, $competencia)) {
                throw new InvalidArgumentException("O cadastro de {$rotulo} não existe mais. Crie-o em \"Novo cadastro\".");
            }

            $existentes = static::consultaAtivos($capitania->id, $competencia)->with('prestador')->lockForUpdate()->get();
            $editaveis = $existentes->where('status', '!=', AgendamentoMarinha::STATUS_AGENDADO);

            // 1) Procurador que saiu do cadastro: só se nenhum agendamento dele já estiver marcado.
            $prestadoresNoFormulario = collect($procuradores)->pluck('prestador_id')->all();

            foreach ($existentes->whereNotIn('prestador_id', $prestadoresNoFormulario) as $agendamento) {
                if ($agendamento->status === AgendamentoMarinha::STATUS_AGENDADO) {
                    throw new InvalidArgumentException("{$agendamento->prestador?->nome} tem agendamento já marcado no SISAP (nº {$agendamento->numero}) "
                        . 'e não pode sair do cadastro. Exclua esse agendamento na tela do mês antes.');
                }
            }

            // 2) Clientes que saíram (dos agendamentos ainda não marcados). Remover antes de gravar
            //    evita conflito de GRU quando um cliente muda de agendamento.
            $clientesNoFormulario = collect($procuradores)
                ->flatMap(fn(array $bloco) => collect(range(1, self::AGENDAMENTOS_POR_PROCURADOR))
                    ->flatMap(fn(int $ordem) => $bloco["agendamento_{$ordem}"]['clientes']))
                ->pluck('id')
                ->filter()
                ->all();

            SolicitacaoAgendamento::query()
                ->whereIn('agendamento_marinha_id', $editaveis->pluck('id'))
                ->whereNotIn('id', $clientesNoFormulario)
                ->delete();

            // 3) Cada procurador: 1º e 2º agendamento com os clientes do formulário.
            foreach ($procuradores as $bloco) {
                $doProcurador = $existentes->where('prestador_id', $bloco['prestador_id'])->keyBy('id');

                for ($ordem = 1; $ordem <= self::AGENDAMENTOS_POR_PROCURADOR; $ordem++) {
                    $slot = $bloco["agendamento_{$ordem}"];
                    $agendamento = $slot['id'] ? $doProcurador->get($slot['id']) : null;

                    // Marcado no SISAP: número, chave, data e clientes ficam como estão.
                    if ($agendamento?->status === AgendamentoMarinha::STATUS_AGENDADO) {
                        continue;
                    }

                    if ($slot['clientes'] === []) {
                        continue; // agendamento vazio é descartado no passo 4
                    }

                    $agendamento ??= AgendamentoMarinha::create([
                        'prestador_id' => $bloco['prestador_id'],
                        'capitania_id' => $capitania->id,
                        'competencia' => $competencia,
                        'ordem' => static::proximaOrdem($bloco['prestador_id'], $capitania->id, $competencia),
                        'status' => AgendamentoMarinha::STATUS_PENDENTE,
                    ]);

                    $agendamento->update(['data_sugerida' => $bloco['data_sugerida'], 'periodo' => $bloco['periodo']]);

                    foreach ($slot['clientes'] as $cliente) {
                        $solicitacao = $cliente['id']
                            ? SolicitacaoAgendamento::whereKey($cliente['id'])->whereIn('agendamento_marinha_id', $editaveis->pluck('id'))->first()
                            : null;

                        // Capitania e mês só entram ao criar o cliente: a chave nunca é regravada.
                        ($solicitacao ?? new SolicitacaoAgendamento([
                            'capitania_id' => $capitania->id,
                            'competencia' => $competencia,
                        ]))->fill([
                            'agendamento_marinha_id' => $agendamento->id,
                            'prestador_id' => $bloco['prestador_id'],
                            'cliente_cpf' => $cliente['cliente_cpf'],
                            'cliente_nome' => $cliente['cliente_nome'],
                            'gru' => $cliente['gru'],
                            'sisap_servico_id' => $cliente['sisap_servico_id'],
                        ])->save();
                    }
                }
            }

            // 4) Agendamentos não marcados que ficaram sem clientes (procurador removido, agendamento esvaziado).
            static::consultaAtivos($capitania->id, $competencia)
                ->where('status', '!=', AgendamentoMarinha::STATUS_AGENDADO)
                ->whereDoesntHave('solicitacoes')
                ->delete();
        });
    }

    /**
     * Normaliza o estado do formulário (repeaters vêm com chaves aleatórias e campos opcionais ausentes).
     */
    private static function normalizar(array $procuradores): array
    {
        return collect($procuradores)->map(function ($bloco) {
            $bloco = (array) $bloco;
            $normalizado = [
                'prestador_id' => (int) ($bloco['prestador_id'] ?? 0),
                'data_sugerida' => filled($bloco['data_sugerida'] ?? null) ? Carbon::parse($bloco['data_sugerida'])->toDateString() : null,
                'periodo' => in_array($bloco['periodo'] ?? null, array_keys(AgendamentoMarinha::periodos()), true) ? $bloco['periodo'] : null,
            ];

            for ($ordem = 1; $ordem <= self::AGENDAMENTOS_POR_PROCURADOR; $ordem++) {
                $slot = (array) ($bloco["agendamento_{$ordem}"] ?? []);

                $normalizado["agendamento_{$ordem}"] = [
                    'id' => filled($slot['id'] ?? null) ? (int) $slot['id'] : null,
                    'clientes' => collect($slot['clientes'] ?? [])->map(fn($c) => [
                        'id' => filled($c['id'] ?? null) ? (int) $c['id'] : null,
                        'cliente_cpf' => SolicitacaoAgendamento::somenteDigitos($c['cliente_cpf'] ?? ''),
                        'cliente_nome' => filled($c['cliente_nome'] ?? null) ? trim($c['cliente_nome']) : null,
                        'gru' => SolicitacaoAgendamento::somenteDigitos($c['gru'] ?? ''),
                        'sisap_servico_id' => filled($c['sisap_servico_id'] ?? null) ? (int) $c['sisap_servico_id'] : null,
                    ])->values()->all(),
                ];
            }

            return $normalizado;
        })->values()->all();
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function validar(Capitania $capitania, string $competencia, array $procuradores, string $rotulo): void
    {
        $vagas = max(1, (int) $capitania->sisap_vagas_por_agendamento);
        $limite = max(1, (int) $capitania->sisap_agendamentos_por_mes);
        $mes = Carbon::parse($competencia)->format('Y-m');

        $idsProcuradores = collect($procuradores)->pluck('prestador_id');

        if ($idsProcuradores->contains(0)) {
            throw new InvalidArgumentException('Escolha o procurador de todos os blocos.');
        }

        if ($idsProcuradores->duplicates()->isNotEmpty()) {
            $nome = Prestador::find($idsProcuradores->duplicates()->first())?->nome ?? 'Um procurador';
            throw new InvalidArgumentException("{$nome} aparece em mais de um bloco. Junte os agendamentos dele num só bloco.");
        }

        $procuradoresValidos = Prestador::where('is_procurador', true)->whereIn('id', $idsProcuradores)->pluck('nome', 'id');
        $grus = [];

        foreach ($procuradores as $bloco) {
            $nome = $procuradoresValidos[$bloco['prestador_id']] ?? null;

            if (!$nome) {
                throw new InvalidArgumentException('Um dos procuradores escolhidos não está cadastrado como procurador.');
            }

            if ($bloco['data_sugerida'] && substr($bloco['data_sugerida'], 0, 7) !== $mes) {
                throw new InvalidArgumentException("{$nome}: a data sugerida precisa estar dentro de {$rotulo}.");
            }

            $slotAnteriorPreenchido = true;

            for ($ordem = 1; $ordem <= self::AGENDAMENTOS_POR_PROCURADOR; $ordem++) {
                $slot = $bloco["agendamento_{$ordem}"];
                $preenchido = $slot['id'] !== null || $slot['clientes'] !== [];

                if ($preenchido && !$slotAnteriorPreenchido) {
                    throw new InvalidArgumentException("{$nome}: preencha o " . ($ordem - 1) . "º agendamento antes do {$ordem}º.");
                }

                if ($slot['clientes'] !== [] && $ordem > $limite) {
                    throw new InvalidArgumentException("{$nome}: {$capitania->sigla} permite só {$limite} agendamento(s) por procurador no mês.");
                }

                if (count($slot['clientes']) > $vagas) {
                    throw new InvalidArgumentException("{$nome}: o {$ordem}º agendamento tem " . count($slot['clientes']) . " clientes, mas {$capitania->sigla} permite {$vagas} por agendamento.");
                }

                foreach ($slot['clientes'] as $cliente) {
                    if (!SolicitacaoAgendamento::cpfValido($cliente['cliente_cpf'])) {
                        throw new InvalidArgumentException("{$nome}: CPF inválido no {$ordem}º agendamento.");
                    }

                    if (strlen($cliente['gru']) !== 18) {
                        throw new InvalidArgumentException("{$nome}: a GRU {$cliente['gru']} precisa ter 18 dígitos.");
                    }

                    if (!$cliente['sisap_servico_id']) {
                        throw new InvalidArgumentException("{$nome}: escolha o serviço de todos os clientes.");
                    }

                    if (isset($grus[$cliente['gru']])) {
                        throw new InvalidArgumentException("A GRU {$cliente['gru']} foi informada mais de uma vez.");
                    }

                    $grus[$cliente['gru']] = $cliente['id'];
                }

                $slotAnteriorPreenchido = $preenchido;
            }
        }

        // A GRU não pode repetir no mesmo mês: em outra capitania, num agendamento já marcado (que não é
        // regravado) ou num cancelado antigo. Em outro mês pode (processo não atendido). Os clientes ainda
        // não marcados deste cadastro são regravados a partir do formulário, então não contam.
        if ($grus !== []) {
            $conflito = SolicitacaoAgendamento::with(['capitania', 'agendamento'])
                ->whereIn('gru', array_keys($grus))
                ->whereDate('competencia', $competencia)
                ->get()
                ->first(fn(SolicitacaoAgendamento $s) => $s->capitania_id !== $capitania->id
                    || $s->agendamento?->status === AgendamentoMarinha::STATUS_CANCELADO
                    || ($s->agendamento?->status === AgendamentoMarinha::STATUS_AGENDADO && $grus[$s->gru] !== $s->id));

            if ($conflito) {
                throw new InvalidArgumentException("A GRU {$conflito->gru} já está num agendamento de {$conflito->capitania?->sigla} em "
                    . $conflito->competencia->format('m/Y') . ($conflito->cliente_nome ? " ({$conflito->cliente_nome})" : '')
                    . '. Uma GRU só pode entrar uma vez por mês.');
            }
        }
    }

    private static function consultaAtivos(int $capitaniaId, string $competencia): Builder
    {
        return AgendamentoMarinha::query()
            ->where('capitania_id', $capitaniaId)
            ->whereDate('competencia', $competencia)
            ->where('status', '!=', AgendamentoMarinha::STATUS_CANCELADO);
    }

    /** A ordem conta também os cancelados, para o número nunca se repetir no mês. */
    private static function proximaOrdem(int $prestadorId, int $capitaniaId, string $competencia): int
    {
        return (int) AgendamentoMarinha::query()
            ->where('prestador_id', $prestadorId)
            ->where('capitania_id', $capitaniaId)
            ->whereDate('competencia', $competencia)
            ->max('ordem') + 1;
    }
}
