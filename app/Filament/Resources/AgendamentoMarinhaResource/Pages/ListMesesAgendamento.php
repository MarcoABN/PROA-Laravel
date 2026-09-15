<?php

namespace App\Filament\Resources\AgendamentoMarinhaResource\Pages;

use App\Filament\Resources\AgendamentoMarinhaResource;
use App\Models\AgendamentoMarinha;
use App\Support\AcessoAgendamento;
use App\Support\EnderecoPublico;
use App\Support\ExtensaoChrome;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Tela de entrada: um card por mês com o andamento e os cadastros de cada capitania.
 * "Novo cadastro" abre o cadastro de uma Capitania + Mês.
 */
class ListMesesAgendamento extends Page
{
    protected static string $resource = AgendamentoMarinhaResource::class;

    protected static string $view = 'filament.resources.agendamento-marinha.meses';

    protected static ?string $title = 'Agendamentos Marinha';

    // Página própria do Resource: o Filament não aplica o canAccess do Resource aqui sozinho.
    public static function canAccess(array $parameters = []): bool
    {
        return AcessoAgendamento::permitido();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getMesesProperty(): Collection
    {
        $servicos = DB::table('agendamento_solicitacoes')
            ->selectRaw('competencia, COUNT(*) AS total')
            ->groupBy('competencia')
            ->pluck('total', 'competencia');

        // Um cadastro por capitania no mês, com link para editar.
        $cadastros = DB::table('agendamentos_marinha')
            ->join('capitanias', 'capitanias.id', '=', 'agendamentos_marinha.capitania_id')
            ->where('agendamentos_marinha.status', '<>', AgendamentoMarinha::STATUS_CANCELADO)
            ->selectRaw('agendamentos_marinha.competencia, capitanias.id, capitanias.sigla, COUNT(*) AS total')
            ->groupBy('agendamentos_marinha.competencia', 'capitanias.id', 'capitanias.sigla')
            ->orderBy('capitanias.sigla')
            ->get()
            ->groupBy('competencia');

        return DB::table('agendamentos_marinha')
            ->selectRaw("
                competencia,
                COUNT(DISTINCT prestador_id) AS procuradores,
                COUNT(*) FILTER (WHERE status <> 'cancelado') AS agendamentos,
                COUNT(*) FILTER (WHERE status = 'agendado') AS agendados,
                COUNT(*) FILTER (WHERE status IN ('pendente', 'falhou')) AS pendentes,
                COUNT(*) FILTER (WHERE status = 'falhou') AS falhas
            ")
            ->groupBy('competencia')
            ->orderByDesc('competencia')
            ->get()
            ->map(function ($mes) use ($servicos, $cadastros) {
                $data = Carbon::parse($mes->competencia);

                return [
                    'cadastros' => ($cadastros[$mes->competencia] ?? collect())
                        ->map(fn($linha) => [
                            'sigla' => $linha->sigla,
                            'total' => (int) $linha->total,
                            'url' => AgendamentoMarinhaResource::urlCadastro((int) $linha->id, $mes->competencia),
                        ])
                        ->values()
                        ->all(),
                    'nome' => ucfirst($data->locale('pt_BR')->translatedFormat('F \d\e Y')),
                    'rotulo' => $data->format('m/Y'),
                    'url' => AgendamentoMarinhaResource::getUrl('mes', ['competencia' => $data->format('Y-m')]),
                    'servicos' => (int) ($servicos[$mes->competencia] ?? 0),
                    'procuradores' => (int) $mes->procuradores,
                    'agendamentos' => (int) $mes->agendamentos,
                    'agendados' => (int) $mes->agendados,
                    'pendentes' => (int) $mes->pendentes,
                    'falhas' => (int) $mes->falhas,
                ];
            });
    }

    /** Aviso no topo da tela para quem usa uma versão antiga da extensão com o próprio token. */
    public function getAlertaExtensaoProperty(): ?string
    {
        return ExtensaoChrome::aviso(Auth::user()?->sisap_extensao_versao);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('baixarExtensao')
                ->label('Baixar extensão')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading('Instalar a extensão do Chrome')
                ->modalDescription(new HtmlString(
                    '<ol style="list-style: decimal; padding-left: 1.25rem; text-align: left; line-height: 1.6;">'
                    . '<li>Baixe o zip e extraia numa pasta fixa, por exemplo <code>C:\PROA</code> (não apague nem mova depois).</li>'
                    . '<li>No Chrome, abra <code>chrome://extensions</code> e ligue o <strong>Modo do desenvolvedor</strong>.</li>'
                    . '<li>Clique em <strong>Carregar sem compactação</strong> e escolha a pasta <code>proa-agendamento-marinha</code>.</li>'
                    . '<li>Clique no ícone da extensão e informe o endereço e o token (botão <strong>Token da extensão</strong>).</li>'
                    . '<li>Para atualizar: extraia o zip novo por cima da mesma pasta e clique em <strong>Recarregar (↻)</strong> na extensão.</li>'
                    . '</ol>'
                ))
                ->modalSubmitActionLabel(fn() => 'Baixar versão ' . (ExtensaoChrome::versaoAtual() ?? ''))
                ->action(fn() => response()
                    ->download(ExtensaoChrome::gerarZip(), ExtensaoChrome::nomeDoArquivo())
                    ->deleteFileAfterSend()),

            Actions\Action::make('tokenExtensao')
                ->label('Token da extensão')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Gerar seu token da extensão')
                ->modalDescription(function () {
                    $geradoEm = Auth::user()?->sisap_token_gerado_em;

                    return 'O token vale para qualquer procurador: a extensão carrega os agendamentos do CPF logado no SISAP. '
                        . ($geradoEm ? "Seu token atual (gerado em {$geradoEm->format('d/m/Y H:i')}) deixará de funcionar." : '');
                })
                ->action(function () {
                    $token = Auth::user()->gerarTokenSisap();

                    Notification::make()
                        ->title('Token gerado — copie agora')
                        ->body(EnderecoPublico::instrucoesExtensao($token))
                        ->persistent()
                        ->success()
                        ->send();
                }),

            Actions\Action::make('novoCadastro')
                ->label('Novo cadastro')
                ->icon('heroicon-o-plus')
                ->url(AgendamentoMarinhaResource::getUrl('cadastro')),
        ];
    }
}
