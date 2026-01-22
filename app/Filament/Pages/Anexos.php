<?php

namespace App\Filament\Pages;

// Imports normais...
use App\Anexos\Anexo2D;
use App\Anexos\Anexo2E;
use App\Anexos\Anexo2J;
use App\Anexos\Anexo2K;
use App\Anexos\Anexo2L;
use App\Anexos\Anexo2M;
use App\Anexos\Anexo3C;
use App\Anexos\Anexo3D;
use App\Anexos\Anexo3A;
use App\Anexos\Anexo3B;
use App\Anexos\Anexo5D;
use App\Anexos\Anexo5E;
use App\Anexos\Anexo5H;
use App\Anexos\Anexo1C;
use App\Anexos\Anexo2A;
use App\Anexos\Anexo2B;
use App\Anexos\Anexo2D212;
use App\Anexos\Anexo2E212;
use App\Anexos\Anexo2F212;
use App\Anexos\Procuracao;
use App\Models\Cliente;
use App\Models\Embarcacao;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class Anexos extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationLabel = 'Central de Anexos';
    protected static ?string $title = 'Emissão de Anexos';
    protected static string $view = 'filament.pages.anexos';

    public ?array $data = [];

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
                            ->getSearchResultsUsing(fn(string $search) => Cliente::query()
                                ->where('nome', 'like', "%{$search}%")
                                ->orWhere('cpfcnpj', 'like', "%{$search}%")
                                ->limit(50)->get()
                                ->mapWithKeys(fn(Cliente $c) => [$c->id => "{$c->nome} - {$c->cpfcnpj}"]))
                            ->getOptionLabelUsing(fn($value) => Cliente::find($value)?->nome)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Set $set) => $set('embarcacao_id', null)),

                        Select::make('embarcacao_id')
                            ->label('Embarcação')
                            ->placeholder(fn(Get $get) => $get('cliente_id') ? 'Selecione uma embarcação' : 'Selecione um cliente primeiro')
                            ->options(fn(Get $get) => $get('cliente_id') ? Embarcacao::where('cliente_id', $get('cliente_id'))->pluck('nome_embarcacao', 'id') : [])
                            ->disabled(fn(Get $get) => !$get('cliente_id'))
                            ->searchable()
                            ->live(),
                    ])
                    ->statePath('data')
                    ->columns(2),
            ]);
    }

    // --- MÉTODOS DE CRIAÇÃO DE BOTÕES ---

    // 1. Botão Padrão (Exige Embarcação)
    private function criarBotaoAnexo(string $classeAnexo, string $tituloBotao, string $cor = 'primary'): Action
    {
        $anexo = new $classeAnexo();
        $classeUrl = str_replace('\\', '-', $classeAnexo);

        return Action::make('gerar' . class_basename($classeAnexo))
            ->label($tituloBotao)
            ->icon('heroicon-o-document-text')
            ->color($cor)
            ->disabled(fn() => empty($this->data['embarcacao_id'])) // Trava se não tiver embarcação
            ->modalHeading($anexo->getTitulo())
            ->form($anexo->getFormSchema())
            ->action(function (array $data, Anexos $livewire) use ($classeUrl) {
                $embarcacaoId = $livewire->data['embarcacao_id'];
                $params = array_merge($data, ['embarcacao' => $embarcacaoId]);
                
                $url = route('anexos.gerar_generico', [
                    'classe' => $classeUrl,
                    'embarcacao' => $embarcacaoId // Manda ID Embarcação
                ]);
                $url .= '?' . http_build_query($data);

                return $livewire->js("window.open('{$url}', '_blank');");
            });
    }

    // 2. Novo Botão para Clientes (CHA e Procurações)
    private function criarBotaoAnexoCliente(string $classeAnexo, string $tituloBotao, string $cor = 'success'): Action
    {
        $anexo = new $classeAnexo();
        $classeUrl = str_replace('\\', '-', $classeAnexo);

        return Action::make('gerar' . class_basename($classeAnexo))
            ->label($tituloBotao)
            ->icon('heroicon-o-user')
            ->color($cor)
            ->disabled(fn() => empty($this->data['cliente_id'])) // Trava apenas se não tiver CLIENTE
            ->modalHeading($anexo->getTitulo())
            ->form($anexo->getFormSchema())
            ->action(function (array $data, Anexos $livewire) use ($classeUrl) {
                $clienteId = $livewire->data['cliente_id'];
                
                // Rota genérica enviando o ID do Cliente + flag ?tipo=cliente
                $url = route('anexos.gerar_generico', [
                    'classe' => $classeUrl,
                    'embarcacao' => $clienteId // Aqui vai o ID do Cliente
                ]);
                
                // Adiciona flag tipo=cliente na query string
                $queryParams = array_merge($data, ['tipo' => 'cliente']);
                $url .= '?' . http_build_query($queryParams);

                return $livewire->js("window.open('{$url}', '_blank');");
            });
    }

    // --- AÇÕES ---

    // Grupo CHA (Agora usam criarBotaoAnexoCliente)
    public function gerarAnexo3AAction(): Action { return $this->criarBotaoAnexoCliente(Anexo3A::class, 'Anexo 3A'); }
    public function gerarAnexo3BAction(): Action { return $this->criarBotaoAnexoCliente(Anexo3B::class, 'Anexo 3B'); }
    public function gerarAnexo5DAction(): Action { return $this->criarBotaoAnexoCliente(Anexo5D::class, 'Anexo 5D'); }
    public function gerarAnexo5EAction(): Action { return $this->criarBotaoAnexoCliente(Anexo5E::class, 'Anexo 5E'); }
    public function gerarAnexo5HAction(): Action { return $this->criarBotaoAnexoCliente(Anexo5H::class, 'Anexo 5H'); }

    // Grupo Procuração (Também só precisa de cliente)
    public function gerarProcuracaoAction(): Action { return $this->criarBotaoAnexoCliente(Procuracao::class, 'Procuração', 'danger'); }

    // Grupos que exigem Embarcação (Mantém criarBotaoAnexo)
    public function gerarAnexo2DAction(): Action { return $this->criarBotaoAnexo(Anexo2D::class, 'Anexo 2D', 'info'); }
    public function gerarAnexo2EAction(): Action { return $this->criarBotaoAnexo(Anexo2E::class, 'Anexo 2E', 'info'); }
    public function gerarAnexo2JAction(): Action { return $this->criarBotaoAnexo(Anexo2J::class, 'Anexo 2J', 'info'); }
    public function gerarAnexo2KAction(): Action { return $this->criarBotaoAnexo(Anexo2K::class, 'Anexo 2K', 'info'); }
    public function gerarAnexo2LAction(): Action { return $this->criarBotaoAnexo(Anexo2L::class, 'Anexo 2L', 'info'); }
    public function gerarAnexo2MAction(): Action { return $this->criarBotaoAnexo(Anexo2M::class, 'Anexo 2M', 'info'); }
    public function gerarAnexo3CAction(): Action { return $this->criarBotaoAnexo(Anexo3C::class, 'Anexo 3C', 'info'); }
    public function gerarAnexo3DAction(): Action { return $this->criarBotaoAnexo(Anexo3D::class, 'Anexo 3D', 'info'); }
    public function gerarAnexo1CAction(): Action { return $this->criarBotaoAnexo(Anexo1C::class, 'Anexo 1C', 'warning'); }
    public function gerarAnexo2AAction(): Action { return $this->criarBotaoAnexo(Anexo2A::class, 'Anexo 2A', 'warning'); }
    public function gerarAnexo2BAction(): Action { return $this->criarBotaoAnexo(Anexo2B::class, 'Anexo 2B', 'warning'); }
    public function gerarAnexo2D212Action(): Action { return $this->criarBotaoAnexo(Anexo2D212::class, 'Anexo 2D (212)', 'warning'); }
    public function gerarAnexo2E212Action(): Action { return $this->criarBotaoAnexo(Anexo2E212::class, 'Anexo 2E (212)', 'warning'); }
    public function gerarAnexo2F212Action(): Action { return $this->criarBotaoAnexo(Anexo2F212::class, 'Anexo 2F', 'warning'); }
}