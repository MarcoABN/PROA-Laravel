<?php

namespace App\Filament\Widgets;

use App\Models\SimuladoResultado;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSimuladosWidget extends BaseWidget
{
    // Ocupa a largura total
    protected int | string | array $columnSpan = 'full';

    // ORDEM 1: Aparece no TOPO
    protected static ?int $sort = 1;

    protected static ?string $heading = 'Últimos Simulados Realizados';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SimuladoResultado::query()->latest()->limit(5)
            )
            ->columns([
                // Coluna Categoria/Modalidade REMOVIDA conforme solicitado

                Tables\Columns\TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Realizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('placar')
                    ->label('Acertos / Erros')
                    ->state(fn (SimuladoResultado $record) => "{$record->acertos} A / {$record->erros} E")
                    ->description(fn (SimuladoResultado $record) => "Total: {$record->total} questões"),

                Tables\Columns\TextColumn::make('porcentagem')
                    ->label('Nota Final')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 1) . '%')
                    ->badge()
                    ->color(fn (string $state): string => (float)$state >= 50 ? 'success' : 'danger'),

                Tables\Columns\IconColumn::make('aprovado')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
            ])
            ->actions([
                // 
            ])
            ->paginated(false);
    }
}