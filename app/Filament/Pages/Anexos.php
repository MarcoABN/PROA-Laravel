<?php

namespace App\Filament\Pages;

// Imports de Anexos
use App\Anexos\Anexo1C;
use App\Anexos\Anexo2A;
use App\Anexos\Anexo2B;
use App\Anexos\Anexo2D;
use App\Anexos\Anexo2D212;
use App\Anexos\Anexo2E;
use App\Anexos\Anexo2E212;
use App\Anexos\Anexo2F212;
use App\Anexos\Anexo2J;
use App\Anexos\Anexo2K;
use App\Anexos\Anexo2L;
use App\Anexos\Anexo2M;
use App\Anexos\Anexo3A;
use App\Anexos\Anexo3B;
use App\Anexos\Anexo3C;
use App\Anexos\Anexo3D;
use App\Anexos\Anexo5D;
use App\Anexos\Anexo5E;
use App\Anexos\Anexo5H;
use App\Anexos\Bsade;
use App\Anexos\DeclaracaoResidencia;
use App\Anexos\Procuracao;
use App\Anexos\RequerimentoServico;
use App\Anexos\DeclaracaoPerda;
use App\Anexos\ComunicadoTransferencia;
use App\Anexos\AutorizacaoTransferencia;
use App\Anexos\TermoResponsabilidade;
use App\Anexos\DeclaracaoPerdaMotoaquatica;
use App\Anexos\AutorizacaoTransferenciaMotoaquatica;
use App\Anexos\ComunicadoTransferenciaMotoaquatica;

// Infra e Models
use App\Models\Cliente;
use App\Models\Embarcacao;
use App\Models\Capitania;
use App\Models\Processo;
use Filament\Actions\Action;
use Filament\Forms\Components\{Section, Select, Textarea, TextInput, DatePicker};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Anexos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'Central de Anexos';
    protected static ?string $title = 'Emissão de Anexos';
    protected static string $view = 'filament.pages.anexos';

    public ?array $data = [];

    // Array local mantido apenas para referência intra-request, 
    // a persistência real agora é feita via Session.
    public array $checksIgnored = [];

    protected $listeners = ['executarRegistroProcesso' => 'registrarProcesso'];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Seleção de Contexto')
                    ->schema([
                        Select::make('cliente_id')
                            ->label('Cliente (Nome ou CPF/CNPJ)')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $numbers = preg_replace('/[^0-9]/', '', $search);

                                if (strlen($search) < 3 && empty($numbers)) {
                                    return [];
                                }

                                return Cliente::query()
                                    ->where(function (Builder $query) use ($search, $numbers) {
                                        if (strlen($search) >= 3) {
                                            $query->where('nome', 'ilike', "%{$search}%");
                                        }
                                        if (!empty($numbers)) {
                                            $query->orWhereRaw("REGEXP_REPLACE(cpfcnpj, '[^0-9]', '', 'g') LIKE ?", ["%{$numbers}%"]);
                                        }
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn(Cliente $c) => [$c->id => "{$c->nome} - {$c->cpfcnpj}"]);
                            })
                            ->getOptionLabelUsing(fn($value) => ($c = Cliente::find($value)) ? "{$c->nome} - {$c->cpfcnpj}" : null)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('embarcacao_id', null);
                            }),

                        Select::make('embarcacao_id')
                            ->label('Embarcação')
                            ->placeholder(fn(Get $get) => $get('cliente_id') ? 'Selecione uma embarcação' : 'Selecione um cliente primeiro')
                            ->options(fn(Get $get) => $get('cliente_id') ? Embarcacao::where('cliente_id', $get('cliente_id'))->pluck('nome_embarcacao', 'id') : [])
                            ->disabled(fn(Get $get) => !$get('cliente_id'))
                            ->searchable()
                            ->live(),
                    ])->statePath('data')->columns(2),
            ]);
    }

    /**
     * Verifica a existência do processo com blindagem contra falhas de conexão/componente.
     */
    public function verificarOuCriarProcesso(string $tipoServico, $clienteId, $embarcacaoId = null)
    {
        $checkKey = md5("check_{$clienteId}_{$tipoServico}");
        $shownKey = "shown_{$checkKey}";

        // 1. BLINDAGEM DE SESSÃO: 
        // Verifica se o usuário já ignorou isso anteriormente. 
        // A sessão persiste mesmo se o componente crashar no front-end.
        $ignorados = session()->get('anexos_ignored_checks', []);
        if (in_array($checkKey, $ignorados)) {
            return;
        }

        // 2. BLINDAGEM DE CACHE (Throttling):
        // Impede que o balão apareça 2x seguidas em menos de 15 segundos.
        if (Cache::has($shownKey)) {
            return;
        }

        $existe = Processo::where('cliente_id', $clienteId)
            ->where('tipo_servico', $tipoServico)
            ->whereNotIn('status', ['concluido', 'arquivado'])
            ->exists();

        if (!$existe) {
            // Marca no cache que acabamos de mostrar (trava visual)
            Cache::put($shownKey, true, 15);

            Notification::make("notif_{$checkKey}")
                ->warning()
                ->title('Processo não identificado')
                ->body("Não encontramos um processo de **{$tipoServico}** ativo. Deseja registrar agora?")
                ->duration(10000) // Fecha automaticamente em 10s
                ->actions([
                    NotificationAction::make('confirmar')
                        ->label('Sim, registrar')
                        ->button()
                        ->color('success')
                        ->close() // Fecha visualmente na hora
                        ->dispatch('executarRegistroProcesso', [
                            'tipo' => $tipoServico,
                            'clienteId' => $clienteId,
                            'embarcacaoId' => $embarcacaoId,
                            'checkKey' => $checkKey,
                        ]),

                    NotificationAction::make('cancelar')
                        ->label('Não')
                        ->color('gray')
                        ->close() // Fecha visualmente na hora (Client-side)
                        ->action(function () use ($checkKey) {
                            // Salva a decisão na SESSÃO do servidor
                            session()->push('anexos_ignored_checks', $checkKey);
                        }),
                ])
                ->send();
        }
    }

    public function registrarProcesso($tipo, $clienteId, $embarcacaoId = null, $checkKey = null): void
    {
        // Ao registrar, também salvamos na sessão para não perguntar de novo
        if ($checkKey) {
            session()->push('anexos_ignored_checks', $checkKey);
        }

        $lockKey = "lock_create_{$clienteId}_" . Str::slug($tipo);
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            return;
        }

        try {
            $user = auth()->user();
            $prazo = now()->addDays(45);

            $processo = DB::transaction(function () use ($tipo, $clienteId, $embarcacaoId, $user, $prazo) {
                $existing = Processo::where('cliente_id', $clienteId)
                    ->where('tipo_servico', $tipo)
                    ->whereNotIn('status', ['concluido', 'arquivado'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $novo = Processo::create([
                    'cliente_id' => $clienteId,
                    'embarcacao_id' => $embarcacaoId,
                    'user_id' => $user->id,
                    'titulo' => "Cadastro automático",
                    'tipo_servico' => $tipo,
                    'status' => 'triagem',
                    'prioridade' => 'normal',
                    'prazo_estimado' => $prazo,
                ]);

                $novo->andamentos()->create([
                    'user_id' => $user->id,
                    'tipo' => 'movimentacao',
                    'descricao' => "Processo iniciado automaticamente por {$user->name}.",
                ]);

                return $novo;
            });

            // Limpa pendências visuais extras
            $this->dispatch('close-notifications');

            if ($processo->wasRecentlyCreated) {
                Notification::make()
                    ->success()
                    ->title('Processo e andamento registrados!')
                    ->send();
            } else {
                Notification::make()
                    ->info()
                    ->title('Processo já existente vinculado.')
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()->danger()->title('Erro ao salvar')->body($e->getMessage())->send();
        } finally {
            $lock->release();
        }
    }

    // --- MÉTODOS AUXILIARES E ACTIONS ---

    private function criarBotaoAnexo(string $classeAnexo, string $tituloBotao, string $tipoServico, string $cor = 'primary'): Action
    {
        $anexo = new $classeAnexo();
        $classeUrl = str_replace('\\', '-', $classeAnexo);

        return Action::make('gerar' . class_basename($classeAnexo))
            ->label($tituloBotao)->icon('heroicon-o-document-text')->color($cor)
            ->disabled(fn() => empty($this->data['embarcacao_id']))
            ->modalHeading($anexo->getTitulo())
            ->form($anexo->getFormSchema())
            ->action(function (array $data, Anexos $livewire) use ($classeUrl, $tipoServico) {
                $clienteId = $livewire->data['cliente_id'];
                $embarcacaoId = $livewire->data['embarcacao_id'];

                $livewire->verificarOuCriarProcesso($tipoServico, $clienteId, $embarcacaoId);

                $url = route('anexos.gerar_generico', ['classe' => $classeUrl, 'embarcacao' => $embarcacaoId]) . '?' . http_build_query($data);

                // CORREÇÃO: setTimeout para evitar o crash do componente ao abrir nova aba
                return $livewire->js("setTimeout(() => window.open('{$url}', '_blank'), 500);");
            });
    }

    private function criarBotaoAnexoCliente(string $classeAnexo, string $tituloBotao, string $tipoServico, string $cor = 'success'): Action
    {
        $anexo = new $classeAnexo();
        $classeUrl = str_replace('\\', '-', $classeAnexo);

        return Action::make('gerar' . class_basename($classeAnexo) . 'Cliente')
            ->label($tituloBotao)->icon('heroicon-o-user')->color($cor)
            ->disabled(fn() => empty($this->data['cliente_id']))
            ->modalHeading($anexo->getTitulo())
            ->form($anexo->getFormSchema())
            ->action(function (array $data, Anexos $livewire) use ($classeUrl, $tipoServico) {
                $clienteId = $livewire->data['cliente_id'];

                $livewire->verificarOuCriarProcesso($tipoServico, $clienteId, null);

                $url = route('anexos.gerar_generico', ['classe' => $classeUrl, 'embarcacao' => $clienteId]) . '?' . http_build_query(array_merge($data, ['tipo' => 'cliente']));

                // CORREÇÃO: setTimeout para evitar o crash do componente
                return $livewire->js("setTimeout(() => window.open('{$url}', '_blank'), 500);");
            });
    }

    // --- GRUPOS CHA, TIE, MOTOAQUÁTICA ---
    public function gerarAnexo3AClienteAction(): Action
    {
        return $this->criarBotaoAnexoCliente(Anexo3A::class, 'Anexo 3A', Processo::TIPO_CHA);
    }
    public function gerarAnexo3BClienteAction(): Action
    {
        return $this->criarBotaoAnexoCliente(Anexo3B::class, 'Anexo 3B', Processo::TIPO_CHA);
    }
    public function gerarAnexo5DClienteAction(): Action
    {
        return $this->criarBotaoAnexoCliente(Anexo5D::class, 'Anexo 5D', Processo::TIPO_CHA);
    }
    public function gerarAnexo5EClienteAction(): Action
    {
        return $this->criarBotaoAnexoCliente(Anexo5E::class, 'Anexo 5E', Processo::TIPO_CHA);
    }
    public function gerarAnexo5HClienteAction(): Action
    {
        return $this->criarBotaoAnexoCliente(Anexo5H::class, 'Anexo 5H', Processo::TIPO_CHA);
    }

    //TESTE DE MUDANÇA
    public function gerarDeclaracaoResidenciaClienteAction(): Action
    {
        return $this->criarBotaoAnexoCliente(DeclaracaoResidencia::class, 'Anexo 2G', Processo::TIPO_CHA);
    }

    public function gerarBsadeAction(): Action
    {
        return $this->criarBotaoAnexo(Bsade::class, 'Anexo 2B', Processo::TIPO_TIE, 'info');
    }

    public function gerarRequerimentoServicoAction(): Action
    {
        return $this->criarBotaoAnexo(RequerimentoServico::class, 'Anexo 2C', Processo::TIPO_TIE, 'info');
    }
    public function gerarDeclaracaoPerdaAction(): Action
    {
        return $this->criarBotaoAnexo(DeclaracaoPerda::class, 'Anexo 2H', Processo::TIPO_TIE, 'info');
    }
    public function gerarComunicadoTransferenciaAction(): Action
    {
        return $this->criarBotaoAnexo(ComunicadoTransferencia::class, 'Anexo 2L', Processo::TIPO_TIE, 'info');
    }
    public function gerarDeclaracaoResidenciaAction(): Action
    {
        return $this->criarBotaoAnexo(DeclaracaoResidencia::class, 'Anexo 2G', Processo::TIPO_TIE, 'info');
    }
    public function gerarAutorizacaoTransferenciaAction(): Action
    {
        return $this->criarBotaoAnexo(AutorizacaoTransferencia::class, 'Anexo 2K', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo3CAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo3C::class, 'Anexo 3C', Processo::TIPO_TIE, 'info');
    }
    public function gerarTermoResponsabilidadeAction(): Action
    {
        return $this->criarBotaoAnexo(TermoResponsabilidade::class, 'Anexo 3C', Processo::TIPO_TIE, 'info');
    }

    public function gerarAnexo1CAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo1C::class, 'Anexo 1C', Processo::TIPO_MOTO, 'warning');
    }
    public function gerarAnexo2AAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2A::class, 'Anexo 2A', Processo::TIPO_MOTO, 'warning');
    }
    public function gerarAnexo2BAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2B::class, 'Anexo 2B', Processo::TIPO_MOTO, 'warning');
    }
    public function gerarDeclaracaoPerdaMotoaquaticaAction(): Action
    {
        return $this->criarBotaoAnexo(DeclaracaoPerdaMotoaquatica::class, 'Anexo 2C (212)', Processo::TIPO_MOTO, 'warning');
    }

    public function gerarAutorizacaoTransferenciaMotoaquaticaAction(): Action
    {
        return $this->criarBotaoAnexo(AutorizacaoTransferenciaMotoaquatica::class, 'Anexo 2D (212)', Processo::TIPO_MOTO, 'warning');
    }

    public function gerarComunicadoTransferenciaMotoaquaticaAction(): Action
    {
        return $this->criarBotaoAnexo(ComunicadoTransferenciaMotoaquatica::class, 'Anexo 2E (212)', Processo::TIPO_MOTO, 'warning');
    }

    // --- GRUPO: ADMINISTRATIVOS ---
    public function gerarProcuracaoClienteAction(): Action
    {
        $anexo = new Procuracao();
        $classeUrl = str_replace('\\', '-', Procuracao::class);
        return Action::make('gerarProcuracaoCliente')
            ->label('Representação')
            ->icon('heroicon-o-user')
            ->color('danger')
            ->disabled(fn() => empty($this->data['cliente_id']))
            ->modalHeading($anexo->getTitulo())
            ->form($anexo->getFormSchema())
            ->action(function (array $data, Anexos $livewire) use ($classeUrl) {
                $livewire->verificarOuCriarProcesso('Representação', $livewire->data['cliente_id'], null);

                $url = route('anexos.gerar_generico', ['classe' => $classeUrl, 'embarcacao' => $livewire->data['cliente_id']]) . '?' . http_build_query(array_merge($data, ['tipo' => 'cliente']));

                // CORREÇÃO: setTimeout
                return $livewire->js("setTimeout(() => window.open('{$url}', '_blank'), 500);");
            });
    }

    public function gerarProcuracao02Action(): Action
    {
        return Action::make('gerarProcuracao02')
            ->label('Emitir Procuração')
            ->icon('heroicon-o-document-text')
            ->color('danger')
            ->disabled(fn() => empty($this->data['cliente_id']))
            ->action(function (Anexos $livewire) {
                $livewire->verificarOuCriarProcesso('Representação', $livewire->data['cliente_id'], $livewire->data['embarcacao_id'] ?? null);

                $url = route('clientes.procuracao', ['id' => $livewire->data['cliente_id'], 'embarcacao_id' => $livewire->data['embarcacao_id'] ?? 'null']);

                // CORREÇÃO: setTimeout
                return $livewire->js("setTimeout(() => window.open('{$url}', '_blank'), 500);");
            });
    }

    public function gerarDefesaInfracaoAction(): Action
    {
        return Action::make('gerarDefesaInfracao')
            ->label('Emitir Defesa')
            ->modalHeading('Defesa de Infração')
            ->icon('heroicon-o-shield-check')
            ->color('danger')
            ->disabled(fn() => empty($this->data['cliente_id']))
            ->form([
                Select::make('capitania_id')->label('Capitania')->options(Capitania::query()->pluck('nome', 'id'))->searchable()->preload()->required(),
                TextInput::make('num_notificacao')->label('Número da Notificação')->required(),
                DatePicker::make('data_notificacao')->label('Data da Notificação')->required(),
                Textarea::make('justificativa')->label('Justificativa da Ocorrência')->rows(5)->required(),
            ])
            ->action(function (array $data, Anexos $livewire) {
                $clienteId = $livewire->data['cliente_id'];
                $embarcacaoId = $livewire->data['embarcacao_id'] ?? null;

                $livewire->verificarOuCriarProcesso(Processo::TIPO_DEFESA, $clienteId, $embarcacaoId);

                $url = route('clientes.defesa_infracao', ['id' => $clienteId, 'embarcacao_id' => $embarcacaoId ?? 'null']) . '?' . http_build_query($data);

                // CORREÇÃO: setTimeout
                return $livewire->js("setTimeout(() => window.open('{$url}', '_blank'), 500);");
            });
    }
}