<?php

namespace App\Filament\Widgets;

use App\Models\Cliente;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestClientes extends BaseWidget
{
    protected static ?string $heading = 'Últimos Clientes Cadastrados';
    protected int | string | array $columnSpan = 'full'; // Ocupa a largura toda

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Cliente::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nome')->label('Nome'),
                Tables\Columns\TextColumn::make('email')->label('E-mail'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->date('d/m/Y'),
            ]);
    }
}