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
use App\Anexos\Procuracao;

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

    /**
     * Armazena chaves de verificações que já foram processadas (Sim ou Não).
     * Isso impede que a mensagem reapareça após a ação do usuário, 
     * mas permite que apareça novamente se mudar o cliente/serviço.
     */
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
                                $searchNumeros = preg_replace('/[^0-9]/', '', $search);
                                return Cliente::query()
                                    ->where('nome', 'ilike', "%{$search}%")
                                    ->orWhere('cpfcnpj', 'like', "%{$search}%")
                                    ->orWhere('cpfcnpj', 'like', "%{$searchNumeros}%")
                                    ->limit(50)->get()->mapWithKeys(fn(Cliente $c) => [$c->id => "{$c->nome} - {$c->cpfcnpj}"]);
                            })
                            ->getOptionLabelUsing(fn($value) => ($c = Cliente::find($value)) ? "{$c->nome} - {$c->cpfcnpj}" : null)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('embarcacao_id', null);
                                // Opcional: Limpar verificações ignoradas ao mudar o cliente para forçar rechecagem limpa
                                // $this->checksIgnored = []; 
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
     * Verifica a existência do processo com proteção de estado.
     */
    public function verificarOuCriarProcesso(string $tipoServico, $clienteId, $embarcacaoId = null)
    {
        // 1. GERAÇÃO DE CHAVE ÚNICA DE CONTEXTO
        // Se o usuário mudar o cliente, o hash muda, permitindo nova verificação.
        $checkKey = md5("check_{$clienteId}_{$tipoServico}");

        // 2. BLINDAGEM DE ESTADO (Componente)
        // Se já processamos essa chave nesta sessão do componente, aborta silenciosamente.
        // Isso impede o retorno do "fantasma" após o refresh do Livewire.
        if (in_array($checkKey, $this->checksIgnored)) {
            return;
        }

        // 3. BLINDAGEM DE CACHE (Aplicação)
        // Proteção extra para cliques ultra-rápidos ou múltiplas abas
        if (Cache::has("ignore_{$checkKey}")) {
            return;
        }

        // 4. VERIFICAÇÃO NO BANCO DE DADOS
        $existe = Processo::where('cliente_id', $clienteId)
            ->where('tipo_servico', $tipoServico)
            ->whereNotIn('status', ['concluido', 'arquivado'])
            ->exists();

        if (!$existe) {
            // ID determinístico para permitir fechamento programático
            $notificationId = "notif_{$checkKey}";

            Notification::make($notificationId)
                ->warning()
                ->title('Processo não identificado')
                ->body("Não encontramos um processo de **{$tipoServico}** ativo. Deseja registrar agora?")
                ->duration(15000) // Duração longa ao invés de persistent() para evitar bugs de sessão
                ->actions([
                    NotificationAction::make('confirmar')
                        ->label('Sim, registrar')
                        ->button()
                        ->color('success')
                        // AQUI ESTÁ A MÁGICA VISUAL:
                        // O x-on:click fecha o modal via JS instantaneamente antes do backend responder.
                        ->extraAttributes(['x-on:click' => "\$dispatch('notifications.close', { id: '{$notificationId}' })"])
                        ->dispatch('executarRegistroProcesso', [
                            'tipo' => $tipoServico,
                            'clienteId' => $clienteId,
                            'embarcacaoId' => $embarcacaoId,
                            'checkKey' => $checkKey,         // Passamos a chave para ignorar futuramente
                            'notificationId' => $notificationId, // Passamos o ID para garantir limpeza
                        ]),
                    NotificationAction::make('cancelar')
                        ->label('Não')
                        ->color('gray')
                        // Ao cancelar, também adicionamos à lista para não perguntar de novo
                        ->action(function () use ($checkKey, $notificationId) {
                            $this->checksIgnored[] = $checkKey;
                            $this->dispatch('notifications.close', id: $notificationId);
                        }),
                ])
                ->send();
        }
    }

    /**
     * Registra o processo com proteção de concorrência e limpeza de interface.
     */
    public function registrarProcesso($tipo, $clienteId, $embarcacaoId = null, $checkKey = null, $notificationId = null): void
    {
        // 1. ATUALIZA ESTADO LOCAL IMEDIATAMENTE
        // Adiciona à lista de ignorados para que qualquer re-renderização subsequente
        // saiba que este alerta já foi resolvido.
        if ($checkKey) {
            $this->checksIgnored[] = $checkKey;
            // Cache curto para garantir integridade entre requests muito próximos
            Cache::put("ignore_{$checkKey}", true, 10);
        }

        // 2. ATOMIC LOCK (Proteção contra duplicação física)
        $lockKey = "lock_create_{$clienteId}_" . Str::slug($tipo);
        $lock = Cache::lock($lockKey, 10); // Trava de 10s

        if (!$lock->get()) {
            return; // Se já está executando, para aqui.
        }

        try {
            $user = auth()->user();
            $prazo = now()->addDays(45);

            // 3. TRANSAÇÃO DE BANCO COM LOCK
            $processo = DB::transaction(function () use ($tipo, $clienteId, $embarcacaoId, $user, $prazo) {
                // Verifica novamente dentro da transação para evitar Race Condition no DB
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

            // 4. GARANTIA DE LIMPEZA DA NOTIFICAÇÃO
            // Comando backend para fechar a notificação específica
            if ($notificationId) {
                $this->dispatch('notifications.close', id: $notificationId);
            }

            // Backup: Fecha todas as notificações para limpar a tela
            $this->dispatch('close-notifications');

            // 5. FEEDBACK AO USUÁRIO
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
                return $livewire->js("window.open('{$url}', '_blank');");
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
                return $livewire->js("window.open('{$url}', '_blank');");
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
    public function gerarAnexo2LClienteAction(): Action
    {
        return $this->criarBotaoAnexoCliente(Anexo2L::class, 'Anexo 2L', Processo::TIPO_CHA);
    }

    public function gerarAnexo2DAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2D::class, 'Anexo 2D', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo2EAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2E::class, 'Anexo 2E', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo2JAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2J::class, 'Anexo 2J', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo2KAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2K::class, 'Anexo 2K', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo2LAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2L::class, 'Anexo 2L', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo2MAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo2M::class, 'Anexo 2M', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo3CAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo3C::class, 'Anexo 3C', Processo::TIPO_TIE, 'info');
    }
    public function gerarAnexo3DAction(): Action
    {
        return $this->criarBotaoAnexo(Anexo3D::class, 'Anexo 3D', Processo::TIPO_TIE, 'info');
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
    public function gerarAnexo2D212Action(): Action
    {
        return $this->criarBotaoAnexo(Anexo2D212::class, 'Anexo 2D (212)', Processo::TIPO_MOTO, 'warning');
    }
    public function gerarAnexo2E212Action(): Action
    {
        return $this->criarBotaoAnexo(Anexo2E212::class, 'Anexo 2E (212)', Processo::TIPO_MOTO, 'warning');
    }
    public function gerarAnexo2F212Action(): Action
    {
        return $this->criarBotaoAnexo(Anexo2F212::class, 'Anexo 2F', Processo::TIPO_MOTO, 'warning');
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
                // Ao abrir procuração, também criamos processo de Representação se não houver
                $livewire->verificarOuCriarProcesso('Representação', $livewire->data['cliente_id'], null);

                $url = route('anexos.gerar_generico', ['classe' => $classeUrl, 'embarcacao' => $livewire->data['cliente_id']]) . '?' . http_build_query(array_merge($data, ['tipo' => 'cliente']));
                return $livewire->js("window.open('{$url}', '_blank');");
            });
    }

    public function gerarProcuracao02Action(): Action
    {
        return Action::make('gerarProcuracao02')
            ->label('Representação')
            ->icon('heroicon-o-document-text')
            ->color('danger')
            ->disabled(fn() => empty($this->data['cliente_id']))
            ->action(function (Anexos $livewire) {
                $livewire->verificarOuCriarProcesso('Representação', $livewire->data['cliente_id'], $livewire->data['embarcacao_id'] ?? null);

                $url = route('clientes.procuracao', ['id' => $livewire->data['cliente_id'], 'embarcacao_id' => $livewire->data['embarcacao_id'] ?? 'null']);
                return $livewire->js("window.open('{$url}', '_blank');");
            });
    }

    public function gerarDefesaInfracaoAction(): Action
    {
        return Action::make('gerarDefesaInfracao')
            ->label('Emitir')
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
                return $livewire->js("window.open('{$url}', '_blank');");
            });
    }
}