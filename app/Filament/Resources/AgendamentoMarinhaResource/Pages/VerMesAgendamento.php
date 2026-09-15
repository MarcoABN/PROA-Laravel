<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Models\Capitania;
use App\Support\AcessoAgendamento;
use App\Support\ExtensaoChrome;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Um mês aberto, para consulta e operação: serviços agrupados por capitania · procurador.
 * Incluir ou alterar clientes é no cadastro de cada capitania ("Editar cadastro").
 */
class VerMesAgendamento extends ListRecords
{
    protected static string $resource = AgendamentoMarinhaResource::class;

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
        return 'Agendamentos de ' . $this->dataCompetencia()->format('m/Y');
    }

    /** Mesmo aviso da tela de meses, para quem usa uma versão antiga da extensão com o próprio token. */
    public function getSubheading(): ?string
    {
        return ExtensaoChrome::aviso(Auth::user()?->sisap_extensao_versao);
    }

    public function getBreadcrumbs(): array
    {
        return [
            AgendamentoMarinhaResource::getUrl() => 'Agendamentos Marinha',
            $this->dataCompetencia()->format('m/Y'),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->whereDate('competencia', $this->dataCompetencia()->toDateString());
    }

    protected function getHeaderActions(): array
    {
        $capitaniasDoMes = Capitania::query()
            ->whereIn('id', AgendamentoMarinha::query()
                ->whereDate('competencia', $this->dataCompetencia()->toDateString())
                ->where('status', '!=', AgendamentoMarinha::STATUS_CANCELADO)
                ->select('capitania_id'))
            ->orderBy('sigla')
            ->get();

        return [
            Actions\Action::make('voltar')
                ->label('Meses')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AgendamentoMarinhaResource::getUrl()),

            Actions\ActionGroup::make($capitaniasDoMes
                ->map(fn(Capitania $capitania) => Actions\Action::make("editarCadastro{$capitania->id}")
                    ->label("Editar cadastro {$capitania->sigla}")
                    ->icon('heroicon-o-pencil-square')
                    ->url(AgendamentoMarinhaResource::urlCadastro($capitania->id, $this->dataCompetencia())))
                ->all())
                ->label('Editar cadastro')
                ->icon('heroicon-o-pencil-square')
                ->button()
                ->color('gray')
                ->visible($capitaniasDoMes->isNotEmpty()),

            // Só há novo cadastro no mês enquanto sobrar capitania sem cadastro (a chave capitania + mês não repete).
            Actions\Action::make('novoCadastro')
                ->label('Novo cadastro')
                ->icon('heroicon-o-plus')
                ->url(AgendamentoMarinhaResource::urlNovoCadastro($this->competencia))
                ->visible(Capitania::whereNotIn('id', $capitaniasDoMes->pluck('id'))->exists()),
        ];
    }
}
