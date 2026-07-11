<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\SimuladoResultado;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class RelatorioSimulados extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Estatísticas de Simulados';
    protected static ?string $title = 'Estatísticas de Simulados dos Clientes';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static string $view = 'filament.pages.relatorio-simulados';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Cliente::query()
                    ->select('clientes.*') // Garante que traga os dados do cliente
                    ->whereHas('simulados') // Traz apenas quem fez simulado
                    ->withCount('simulados as total_simulados')
                    ->withCount([
                        'simulados as aprovados_count' => fn (Builder $query) => $query->where('aprovado', true),
                        'simulados as reprovados_count' => fn (Builder $query) => $query->where('aprovado', false),
                    ])
                    // Subquery para descobrir a data do último simulado e ordenar por ela
                    ->selectSub(
                        SimuladoResultado::select('created_at')
                            ->whereColumn('cliente_id', 'clientes.id')
                            ->orderByDesc('created_at')
                            ->limit(1),
                        'ultimo_simulado_em'
                    )
                    ->orderByDesc('ultimo_simulado_em') // O último que fez fica no topo
            )
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome do Cliente')
                    ->searchable()
                    ->weight('bold'),
                    
                TextColumn::make('ultimo_simulado_em')
                    ->label('Último Simulado Realizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('total_simulados')
                    ->label('Qtd. Feita')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('taxa_aprovacao')
                    ->label('Taxa de Aprovação')
                    ->state(function (Cliente $record) {
                        if ($record->total_simulados === 0) return '0%';
                        $taxa = ($record->aprovados_count / $record->total_simulados) * 100;
                        return number_format($taxa, 1, ',', '.') . '%';
                    })
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('taxa_reprovacao')
                    ->label('Taxa de Reprovação')
                    ->state(function (Cliente $record) {
                        if ($record->total_simulados === 0) return '0%';
                        $taxa = ($record->reprovados_count / $record->total_simulados) * 100;
                        return number_format($taxa, 1, ',', '.') . '%';
                    })
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),
            ]);
    }
}