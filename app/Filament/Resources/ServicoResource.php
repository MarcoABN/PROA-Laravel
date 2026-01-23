<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicoResource\Pages;
use App\Filament\Resources\ServicoResource\RelationManagers;
use App\Models\Servico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServicoResource extends Resource
{
    protected static ?string $model = Servico::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Serviços do Site';

    protected static ?string $navigationGroup = 'Painel de Controle';

    protected static ?string $slug = 'servicos-do-site';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\TextInput::make('nome')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn(string $operation, $state, \Filament\Forms\Set $set) =>
                        $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                    ),

                \Filament\Forms\Components\TextInput::make('slug')
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->unique(Servico::class, 'slug', ignoreRecord: true),

                \Filament\Forms\Components\Textarea::make('descricao')
                    ->required()
                    ->columnSpanFull(),

                \Filament\Forms\Components\TextInput::make('icone')
                    ->helperText('Ex: anchor, ship, boat'),

                \Filament\Forms\Components\Toggle::make('ativo')
                    ->label('Ativo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('nome')
                    ->label('Nome do Serviço')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(50) // Limita o texto para não quebrar o layout
                    ->searchable(),

                \Filament\Tables\Columns\IconColumn::make('ativo')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicos::route('/'),
            'create' => Pages\CreateServico::route('/create'),
            'edit' => Pages\EditServico::route('/{record}/edit'),
        ];
    }
}
