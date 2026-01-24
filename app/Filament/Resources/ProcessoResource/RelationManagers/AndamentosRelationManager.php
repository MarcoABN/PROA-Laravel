<?php

namespace App\Filament\Resources\ProcessoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AndamentosRelationManager extends RelationManager
{
    protected static string $relationship = 'andamentos';
    
    // Título da Aba
    protected static ?string $title = 'Timeline / Andamentos';
    protected static ?string $icon = 'heroicon-o-clock';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição do Andamento')
                    ->required()
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('tipo')
                    ->options([
                        'comentario' => 'Comentário Geral',
                        'movimentacao' => 'Movimentação Física',
                        'contato' => 'Contato com Cliente',
                    ])
                    ->default('comentario')
                    ->required(),
                    
                // Captura automaticamente quem escreveu
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descricao')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->width('150px'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Histórico')
                    ->wrap(), // Permite quebra de linha para textos longos
                    
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'movimentacao' => 'warning',
                        'contato' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Novo Andamento')
                    ->slideOver(), // Abre numa janela lateral moderna
            ])
            ->actions([
                // Permite editar/excluir apenas se necessário. 
                // Muitas vezes em timeline, só permitimos Excluir e não Editar para manter integridade.
                //Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc'); // Mais recentes primeiro
    }
}