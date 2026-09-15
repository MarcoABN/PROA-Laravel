<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Support\AcessoAgendamento;
use App\Support\ExtensaoChrome;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Um mês aberto: os serviços do mês agrupados por procurador.
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
        return [
            Actions\Action::make('voltar')
                ->label('Meses')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AgendamentoMarinhaResource::getUrl()),

            Actions\CreateAction::make()
                ->label('Adicionar serviço')
                ->modalHeading('Adicionar serviço neste mês')
                ->using(fn(array $data, Actions\CreateAction $action) => AgendamentoMarinhaResource::salvar($data, null, $action)),
        ];
    }
}
