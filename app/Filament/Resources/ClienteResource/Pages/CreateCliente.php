<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Filament\Resources\EmbarcacaoResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    // Propriedade para controlar o redirecionamento
    protected bool $redirectToBoat = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }

    // Altera o Título grande da página
    public function getTitle(): string
    {
        return 'Cadastrar Cliente';
    }

    // (Opcional) Altera o texto do caminho de navegação
    public function getBreadcrumb(): string
    {
        return 'Cadastrar';
    }

    /**
     * Customiza o botão padrão "Criar" para "Salvar"
     */
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Salvar');
    }

    /**
     * Define os botões do formulário (Salvar, Salvar e Cadastrar Barco, Cancelar)
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),

            // Novo botão personalizado
            Action::make('saveAndCreateBoat')
                ->label('Salvar e Cadastrar Embarcação')
                ->color('warning')
                ->action('saveAndGoToBoat'), // Chama o método abaixo

            $this->getCancelFormAction(),
        ];
    }

    /**
     * Ação disparada pelo novo botão
     */
    public function saveAndGoToBoat(): void
    {
        $this->redirectToBoat = true;
        $this->create(); // Executa a criação padrão do Filament
    }

    /**
     * Lógica de redirecionamento após o sucesso
     */
    protected function getRedirectUrl(): string
    {
        // Se clicou no botão de embarcação, redireciona para a rota create da embarcação com o ID
        if ($this->redirectToBoat) {
            return EmbarcacaoResource::getUrl('create', [
                'cliente_id' => $this->record->id,
            ]);
        }

        // Fluxo normal (volta para o Index)
        return $this->getResource()::getUrl('index');
    }
}