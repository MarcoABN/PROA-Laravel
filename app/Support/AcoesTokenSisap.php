<?php

namespace App\Support;

use App\Models\SisapToken;
use Closure;
use Filament\Actions\MountableAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Ações dos tokens da extensão, iguais na tela de Agendamentos (token do usuário) e na de
 * procuradores (token individual). $dono recebe o registro da linha (null na tela) e devolve o dono.
 */
class AcoesTokenSisap
{
    /** Gera mais um token: um por navegador, sem derrubar os outros. */
    public static function gerar(MountableAction $acao, Closure $dono, string $explicacao): MountableAction
    {
        return $acao
            ->label('Gerar token')
            ->icon('heroicon-o-key')
            ->modalHeading('Gerar token da extensão')
            ->modalDescription($explicacao . ' Os tokens já gerados continuam funcionando: gere um para cada navegador.')
            ->modalSubmitActionLabel('Gerar')
            ->form([
                Forms\Components\TextInput::make('nome')
                    ->label('Navegador')
                    ->placeholder('Ex.: Chrome do escritório – perfil Edivania')
                    ->helperText('Serve para reconhecer o token na hora de revogar.')
                    ->required()
                    ->maxLength(100),
            ])
            ->action(function (array $data, ?Model $record = null) use ($dono) {
                $token = $dono($record)->gerarTokenSisap($data['nome']);

                Notification::make()
                    ->title('Token gerado — copie agora')
                    ->body(EnderecoPublico::instrucoesExtensao($token))
                    ->persistent()
                    ->success()
                    ->send();
            });
    }

    /** Revoga os tokens marcados; os navegadores que usavam param de funcionar na hora. */
    public static function revogar(MountableAction $acao, Closure $dono): MountableAction
    {
        return $acao
            ->label('Revogar tokens')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->modalHeading('Revogar tokens da extensão')
            ->modalDescription('O navegador que usa um token revogado deixa de acessar o PROA na hora.')
            ->modalSubmitActionLabel('Revogar marcados')
            ->visible(fn(?Model $record = null) => $dono($record)?->tokensSisap()->exists() ?? false)
            ->form([
                Forms\Components\CheckboxList::make('tokens')
                    ->label('Tokens')
                    ->options(fn(?Model $record = null) => $dono($record)->tokensSisap()->get()
                        ->mapWithKeys(fn(SisapToken $t) => [$t->id => $t->descricao()])
                        ->all())
                    ->required(),
            ])
            ->action(function (array $data, ?Model $record = null) use ($dono) {
                $revogados = $dono($record)->revogarTokensSisap(array_map('intval', $data['tokens']));

                Notification::make()->title("{$revogados} token(s) revogado(s).")->success()->send();
            });
    }
}
