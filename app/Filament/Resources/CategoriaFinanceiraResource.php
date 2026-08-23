<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaFinanceiraResource\Pages;
use App\Models\CategoriaFinanceira;
use App\Models\LancamentoFinanceiro;
use App\Support\AcessoFinanceiro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriaFinanceiraResource extends Resource
{
    protected static ?string $model = CategoriaFinanceira::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Tipos de Entrada e Despesa';
    protected static ?string $navigationGroup = 'Gestão Financeira';
    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'tipos-financeiros';
    protected static ?string $modelLabel = 'Tipo';
    protected static ?string $pluralModelLabel = 'Tipos de Entrada e Despesa';

    // Sem isto o Filament title-case o rotulo e o menu vira "Tipos De Entrada E Despesa".
    protected static bool $hasTitleCaseModelLabel = false;

    public static function canAccess(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Tipo')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Aplica-se a')
                            ->options(LancamentoFinanceiro::tipos())
                            ->required()
                            ->native(false)
                            ->helperText('Define se este tipo aparece nos lançamentos de entrada ou de saída.'),

                        Forms\Components\TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Ex.: Combustível, Alimentação, Honorários, Taxas da Marinha.'),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true)
                            ->helperText('Tipos inativos deixam de aparecer em novos lançamentos, mas os antigos continuam somando nos relatórios.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(CategoriaFinanceira $record) => $record->descricao),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Aplica-se a')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => LancamentoFinanceiro::tipos()[$state] ?? $state)
                    ->color(fn(string $state): string => $state === LancamentoFinanceiro::TIPO_ENTRADA ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lancamentos_count')
                    ->label('Lançamentos')
                    ->counts('lancamentos')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Aplica-se a')
                    ->options(LancamentoFinanceiro::tipos()),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Ativo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategoriaFinanceiras::route('/'),
            'create' => Pages\CreateCategoriaFinanceira::route('/create'),
            'edit'   => Pages\EditCategoriaFinanceira::route('/{record}/edit'),
        ];
    }
}
