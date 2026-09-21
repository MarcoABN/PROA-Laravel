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
    protected static ?string $modelLabel = 'Serviços do Site';
    protected static ?string $pluralModelLabel = 'Serviços do Site';

    

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

                \Filament\Forms\Components\Section::make('Página do serviço no site')
                    ->description(fn(?Servico $record) => $record?->slug
                        ? 'Publicada em ' . route('site.servico', $record->slug)
                        : 'Cada serviço ativo ganha uma página própria no site, indexada pelo Google.')
                    ->collapsible()
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('titulo_seo')
                            ->label('Título para o Google')
                            ->maxLength(70)
                            ->helperText('Até 70 caracteres. Ex.: "Arrais Amador em Goiânia | Campeão Náutica". Em branco, usa "Nome do serviço em Goiânia | Campeão Náutica".'),

                        \Filament\Forms\Components\Textarea::make('meta_descricao')
                            ->label('Descrição para o Google')
                            ->maxLength(160)
                            ->rows(2)
                            ->helperText('Até 160 caracteres. Texto que aparece abaixo do título na busca. Em branco, usa a descrição acima.'),

                        \Filament\Forms\Components\RichEditor::make('conteudo')
                            ->label('Texto da página')
                            ->toolbarButtons(['h2', 'h3', 'bold', 'italic', 'link', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo'])
                            ->helperText('Explique o serviço com detalhes: para quem é, documentos necessários, etapas e prazos. Páginas com 400 palavras ou mais tendem a se posicionar melhor.')
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Repeater::make('faq')
                            ->label('Perguntas frequentes')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('pergunta')->required(),
                                \Filament\Forms\Components\Textarea::make('resposta')->required()->rows(3),
                            ])
                            ->itemLabel(fn(array $state): ?string => $state['pergunta'] ?? null)
                            ->addActionLabel('Adicionar pergunta')
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
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
