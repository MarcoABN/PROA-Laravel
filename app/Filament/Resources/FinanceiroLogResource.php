<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinanceiroLogResource\Pages;
use App\Models\FinanceiroLog;
use App\Models\User;
use App\Support\AcessoFinanceiro;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tela somente-leitura da trilha de auditoria. Log que pode ser editado ou
 * apagado pela interface não serve como prova de nada, então esta Resource não
 * expõe criação, edição nem exclusão.
 */
class FinanceiroLogResource extends Resource
{
    protected static ?string $model = FinanceiroLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Histórico de Alterações';
    protected static ?string $navigationGroup = 'Gestão Financeira';
    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'historico-financeiro';
    protected static ?string $modelLabel = 'Registro do Histórico';
    protected static ?string $pluralModelLabel = 'Histórico de Alterações';

    // Sem isto o Filament title-case o rótulo e o cabeçalho vira "Histórico De Alterações".
    protected static bool $hasTitleCaseModelLabel = false;

    public static function canAccess(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AcessoFinanceiro::permitido();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->description(fn(FinanceiroLog $record): string => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('user_nome')
                    ->label('Quem')
                    ->searchable()
                    ->weight('bold')
                    // O nome é o retrato gravado na hora; se o usuário sumiu, ainda aparece.
                    ->description(fn(FinanceiroLog $record): ?string => $record->user_id ? null : 'usuário removido ou ação automática'),

                Tables\Columns\TextColumn::make('evento')
                    ->label('O que fez')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => FinanceiroLog::eventos()[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        FinanceiroLog::EVENTO_CRIADO     => 'success',
                        FinanceiroLog::EVENTO_ATUALIZADO => 'warning',
                        FinanceiroLog::EVENTO_EXCLUIDO   => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('registro_descricao')
                    ->label('Registro')
                    ->searchable()
                    ->wrap()
                    ->description(fn(FinanceiroLog $record): string => $record->registro_tipo_rotulo),

                Tables\Columns\TextColumn::make('alteracoes')
                    ->label('Campos')
                    ->state(fn(FinanceiroLog $record): int => count($record->alteracoes ?? []))
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'primary' : 'gray')
                    ->alignCenter()
                    ->tooltip('Quantidade de campos tocados. Clique na linha para ver o antes e o depois.'),

                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('evento')
                    ->label('O que fez')
                    ->options(FinanceiroLog::eventos()),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Quem')
                    ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\SelectFilter::make('registro_tipo')
                    ->label('Tipo de registro')
                    ->options(FinanceiroLog::tiposDeRegistro()),

                Tables\Filters\Filter::make('periodo')
                    ->form([
                        Forms\Components\DatePicker::make('de')
                            ->label('De')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('ate')
                            ->label('Até')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when($data['de'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['ate'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicadores = [];

                        if ($data['de'] ?? null) {
                            $indicadores[] = 'De ' . \Carbon\Carbon::parse($data['de'])->format('d/m/Y');
                        }

                        if ($data['ate'] ?? null) {
                            $indicadores[] = 'Até ' . \Carbon\Carbon::parse($data['ate'])->format('d/m/Y');
                        }

                        return $indicadores;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detalhes'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Nenhuma movimentação registrada ainda')
            ->emptyStateDescription('Toda criação, alteração e exclusão feita na Gestão Financeira aparece aqui.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Movimentação')
                    ->schema([
                        Infolists\Components\TextEntry::make('user_nome')->label('Quem'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Quando')
                            ->dateTime('d/m/Y H:i:s'),
                        Infolists\Components\TextEntry::make('evento')
                            ->label('O que fez')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => FinanceiroLog::eventos()[$state] ?? $state)
                            ->color(fn(string $state): string => match ($state) {
                                FinanceiroLog::EVENTO_CRIADO     => 'success',
                                FinanceiroLog::EVENTO_ATUALIZADO => 'warning',
                                FinanceiroLog::EVENTO_EXCLUIDO   => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('ip')
                            ->label('IP de origem')
                            ->placeholder('não registrado'),
                        Infolists\Components\TextEntry::make('registro_tipo_rotulo')
                            ->label('Tipo de registro'),
                        Infolists\Components\TextEntry::make('registro_descricao')
                            ->label('Registro')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('O que mudou')
                    ->schema([
                        Infolists\Components\ViewEntry::make('alteracoes')
                            ->hiddenLabel()
                            ->view('filament.infolists.alteracoes-financeiras'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceiroLogs::route('/'),
            'view'  => Pages\ViewFinanceiroLog::route('/{record}'),
        ];
    }
}
