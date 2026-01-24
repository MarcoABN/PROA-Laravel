<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PerformanceTable extends BaseWidget
{
    protected static ?string $heading = 'Ranking de Captura de Clientes';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Capture the filter state manually to ensure the column ALWAYS exists
                // This prevents the "Undefined column" error in ORDER BY
                $filters = $this->tableFilters ?? [];
                $dataInicio = $filters['periodo']['data_inicio'] ?? now()->startOfMonth()->toDateString();
                $dataFim = $filters['periodo']['data_fim'] ?? now()->toDateString();

                return User::query()
                    // 1. Standard Count (All Time)
                    ->withCount('clientes')

                    // 2. Period Count (Dynamic based on filter or default to This Month)
                    ->withCount(['clientes as clientes_periodo_count' => function (Builder $query) use ($dataInicio, $dataFim) {
                        if ($dataInicio) {
                            $query->whereDate('created_at', '>=', $dataInicio);
                        }
                        if ($dataFim) {
                            $query->whereDate('created_at', '<=', $dataFim);
                        }
                    }])
                    
                    // 3. Quality Count (With Boat)
                    ->withCount(['clientes as clientes_com_barco_count' => function ($query) {
                        $query->has('embarcacoes');
                    }]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuário')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('clientes_periodo_count')
                    ->label('Cadastros no Período')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable(), // Now safe to sort because the column always exists

                Tables\Columns\TextColumn::make('clientes_com_barco_count')
                    ->label('Total com Embarcação'),
                    //->description('Conversão (Qualidade)'),

                Tables\Columns\TextColumn::make('clientes_count')
                    ->label('Total Histórico')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Membro desde')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('periodo')
                    ->form([
                        DatePicker::make('data_inicio')
                            ->label('De')
                            ->default(now()->startOfMonth()), 
                        DatePicker::make('data_fim')
                            ->label('Até')
                            ->default(now()),
                    ])
                    // We removed the 'query' callback here because we handle the logic 
                    // in the main query() above. The form just holds the state now.
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['data_inicio']) && empty($data['data_fim'])) {
                            return null;
                        }
                        return 'Período Filtrado';
                    }),
            ])
            ->defaultSort('clientes_periodo_count', 'desc')
            ->paginated(false);
    }
}