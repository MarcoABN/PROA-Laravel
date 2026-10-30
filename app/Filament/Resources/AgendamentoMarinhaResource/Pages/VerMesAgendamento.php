<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Models\MesAgendamento;
use App\Services\Agendamento\CadastroDoMes;
use App\Services\Agendamento\VisibilidadeNaExtensao;
use App\Support\AcessoAgendamento;
use App\Support\ExtensaoChrome;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Um mês aberto: um balão por capitania + procurador (clicar abre para preencher), agrupados por capitania.
 * A lista de clientes do mês fica numa tela própria (ClientesDoMesAgendamento), pelo botão no alto.
 */
class VerMesAgendamento extends ListRecords
{
    protected static string $resource = AgendamentoMarinhaResource::class;

    protected static string $view = 'filament.resources.agendamento-marinha.mes';

    /** Mês no formato Y-m, vindo da URL. */
    public string $competencia = '';

    public static function canAccess(array $parameters = []): bool
    {
        return AcessoAgendamento::permitido();
    }

    public function mount(string $competencia = ''): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless((bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $competencia), 404);

        $this->competencia = $competencia;

        parent::mount();
    }

    public function dataCompetencia(): Carbon
    {
        return Carbon::createFromFormat('!Y-m', $this->competencia);
    }

    public function getTitle(): string
    {
        return 'Agendamentos de ' . $this->dataCompetencia()->locale('pt_BR')->translatedFormat('F \d\e Y');
    }

    /** Mesmo aviso da tela de meses, para quem usa uma versão antiga da extensão com o próprio token. */
    public function getSubheading(): ?string
    {
        return ExtensaoChrome::aviso(Auth::user()?->sisap_extensao_versao);
    }

    public function getBreadcrumbs(): array
    {
        return [
            AgendamentoMarinhaResource::getUrl() . '?ano=' . $this->dataCompetencia()->year => 'Agendamentos Marinha',
            $this->dataCompetencia()->format('m/Y'),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->whereDate('competencia', $this->dataCompetencia()->toDateString());
    }

    /**
     * Exclui o agendamento inteiro com todos os clientes. Aberta pelo botão "Excluir Nº" no cabeçalho
     * de cada procurador (AgendamentoMarinhaResource::resumoProcurador).
     */
    public function excluirAgendamentoAction(): Actions\Action
    {
        $agendamento = fn(array $arguments) => AgendamentoMarinha::with('prestador')
            ->whereDate('competencia', $this->dataCompetencia()->toDateString())
            ->find($arguments['agendamento'] ?? null);

        return Actions\Action::make('excluirAgendamento')
            ->requiresConfirmation()
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->modalHeading(fn(array $arguments) => ($a = $agendamento($arguments))
                ? "Excluir o {$a->ordem}º agendamento de {$a->prestador?->nome}?"
                : 'Agendamento não encontrado')
            ->modalDescription(function (array $arguments) use ($agendamento) {
                $a = $agendamento($arguments);

                if (!$a) {
                    return 'Ele pode já ter sido excluído. Atualize a página.';
                }

                $aviso = $a->status === AgendamentoMarinha::STATUS_AGENDADO
                    ? "Ele já foi marcado no SISAP (nº {$a->numero}): cancele também no SISAP, até 24h antes. "
                    : '';

                return $aviso . 'O agendamento e os ' . $a->solicitacoes()->count() . ' cliente(s) dele são apagados do PROA, a cota do procurador é liberada e as GRUs podem ser usadas de novo.';
            })
            ->modalSubmitActionLabel('Excluir agendamento')
            ->action(function (array $arguments) use ($agendamento) {
                $a = $agendamento($arguments);

                if (!$a) {
                    Notification::make()->title('Agendamento não encontrado.')->warning()->send();

                    return;
                }

                $clientes = CadastroDoMes::excluirAgendamento($a);

                Notification::make()->title("Agendamento excluído com {$clientes} cliente(s).")->success()->send();
            });
    }

    /**
     * Os balões do mês: capitania + procurador, com a situação de cada agendamento permitido pela capitania.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getBaloesProperty(): Collection
    {
        return AgendamentoMarinha::query()
            ->with(['capitania', 'prestador'])
            ->withCount(['solicitacoes' => fn(Builder $q) => $q->whereNull('descartada_em')])
            ->whereDate('competencia', $this->dataCompetencia()->toDateString())
            ->where('status', '!=', AgendamentoMarinha::STATUS_CANCELADO)
            ->orderBy('ordem')
            ->get()
            ->groupBy(fn(AgendamentoMarinha $a) => "{$a->capitania_id}-{$a->prestador_id}")
            ->map(function (Collection $agendamentos) {
                $primeiro = $agendamentos->first();
                $capitania = $primeiro->capitania;
                $vagas = $primeiro->vagasTotais();
                $slots = [];

                foreach (range(1, max((int) $capitania->sisap_agendamentos_por_mes, $agendamentos->count())) as $ordem) {
                    $a = $agendamentos->get($ordem - 1);

                    // Rótulos curtos: o balão é estreito e "Aguardando agendamento" era cortado.
                    $status = match (true) {
                        !$a => 'Livre',
                        $a->status === AgendamentoMarinha::STATUS_PENDENTE => 'Aguardando',
                        default => AgendamentoMarinha::statuses()[$a->status] ?? $a->status,
                    };

                    $slots[] = [
                        'ordem' => $ordem,
                        'status' => $status,
                        'cor' => $a ? (AgendamentoMarinha::coresStatus()[$a->status] ?? 'gray') : 'gray',
                        'clientes' => ($a ? $a->solicitacoes_count : 0) . "/{$vagas}",
                        'quando' => $a?->data_hora?->format('d/m H:i'),
                    ];
                }

                return [
                    'url' => AgendamentoMarinhaResource::urlCadastro($capitania->id, $this->dataCompetencia(), $primeiro->prestador_id),
                    'procurador' => $primeiro->prestador?->nome ?? 'Procurador',
                    'capitania' => $capitania->nome,
                    'sigla' => $capitania->sigla,
                    'preferencia' => $agendamentos->firstWhere('status', '!=', AgendamentoMarinha::STATUS_AGENDADO)?->rotuloPreferencia(),
                    'slots' => $slots,
                ];
            })
            ->sortBy(fn(array $balao) => "{$balao['sigla']} {$balao['procurador']}")
            ->values();
    }

    /** Novos agendamentos só no mês atual e nos seguintes. */
    public function podeCriar(): bool
    {
        return $this->dataCompetencia()->gte(now()->startOfMonth());
    }

    /** "Novo agendamento": escolhe capitania e procurador e abre o balão (o mesmo, se já existir). */
    public function novoBalaoAction(): Actions\Action
    {
        return Actions\Action::make('novoBalao')
            ->label('Novo agendamento')
            ->icon('heroicon-o-plus')
            ->visible(fn() => $this->podeCriar())
            ->modalHeading('Novo agendamento de ' . $this->dataCompetencia()->format('m/Y'))
            ->modalDescription('Escolha a capitania e o procurador. Na próxima tela você preenche os clientes.')
            ->modalSubmitActionLabel('Continuar')
            ->modalWidth('md')
            ->form([
                Forms\Components\Select::make('capitania_id')
                    ->label('Capitania')
                    ->options(AgendamentoMarinhaResource::opcoesCapitanias())
                    ->default(Capitania::where('padrao', true)->value('id'))
                    ->native(false)
                    ->required(),
                Forms\Components\Select::make('prestador_id')
                    ->label('Procurador')
                    ->options(AgendamentoMarinhaResource::opcoesProcuradores())
                    ->searchable()
                    ->required(),
            ])
            ->action(fn(array $data) => $this->redirect(AgendamentoMarinhaResource::urlCadastro(
                (int) $data['capitania_id'], $this->dataCompetencia(), (int) $data['prestador_id'],
            )));
    }

    /** Situação do mês na extensão, para a faixa acima dos balões (null em mês que já passou). */
    public function textoExtensao(): ?string
    {
        if (!$this->podeCriar()) {
            return null;
        }

        return VisibilidadeNaExtensao::rotulo(VisibilidadeNaExtensao::motivo($this->dataCompetencia()))
            ?? 'Não aparece na extensão. Use "Exibir na extensão" quando quiser agendar este mês.';
    }

    /** Liga/desliga "Exibir na extensão" do mês. O mês principal aparece sempre e não tem o botão. */
    public function exibirNaExtensaoAction(): Actions\Action
    {
        $ligado = fn() => MesAgendamento::exibirNaExtensao($this->dataCompetencia());

        return Actions\Action::make('exibirNaExtensao')
            ->label(fn() => $ligado() ? 'Ocultar da extensão' : 'Exibir na extensão')
            ->icon(fn() => $ligado() ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
            ->color('gray')
            ->visible(fn() => $this->podeCriar()
                && VisibilidadeNaExtensao::motivo($this->dataCompetencia()) !== VisibilidadeNaExtensao::PRINCIPAL)
            ->action(function () use ($ligado) {
                $exibir = !$ligado();
                MesAgendamento::definirExibirNaExtensao($this->dataCompetencia(), $exibir);

                Notification::make()
                    ->title($exibir
                        ? 'Os agendamentos deste mês agora aparecem na extensão.'
                        : 'Os agendamentos deste mês não aparecem mais na extensão.')
                    ->success()
                    ->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voltar')
                ->label('Calendário')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AgendamentoMarinhaResource::getUrl() . '?ano=' . $this->dataCompetencia()->year),

            Actions\Action::make('clientes')
                ->label('Clientes do mês')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(AgendamentoMarinhaResource::getUrl('clientes', ['competencia' => $this->competencia])),

            $this->exibirNaExtensaoAction(),

            $this->novoBalaoAction(),
        ];
    }
}
