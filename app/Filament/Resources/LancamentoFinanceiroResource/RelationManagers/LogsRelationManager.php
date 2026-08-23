<?php

namespace App\Filament\Resources\LancamentoFinanceiroResource\RelationManagers;

use App\Models\FinanceiroLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Histórico do próprio lançamento, na tela de edição dele.
 * Somente leitura — a trilha é escrita pelo sistema.
 */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Histórico';
    protected static ?string $icon = 'heroicon-o-clock';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i:s')
                    ->description(fn(FinanceiroLog $record): string => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('user_nome')
                    ->label('Quem')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('evento')
                    ->label('O que fez')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => FinanceiroLog::eventos()[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        FinanceiroLog::EVENTO_CRIADO     => 'success',
                        FinanceiroLog::EVENTO_ATUALIZADO => 'warning',
                        FinanceiroLog::EVENTO_EXCLUIDO   => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('alteracoes')
                    ->label('Campos alterados')
                    ->state(fn(FinanceiroLog $record): string => collect($record->alteracoes ?? [])
                        ->pluck('rotulo')
                        ->join(', ') ?: '—')
                    ->wrap(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Sem histórico para este lançamento');
    }
}
