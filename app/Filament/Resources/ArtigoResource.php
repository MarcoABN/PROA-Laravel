<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArtigoResource\Pages;
use App\Models\Artigo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ArtigoResource extends Resource
{
    protected static ?string $model = Artigo::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Blog do Site';

    protected static ?string $navigationGroup = 'Painel de Controle';

    protected static ?string $slug = 'blog-do-site';
    protected static ?string $modelLabel = 'Artigo';
    protected static ?string $pluralModelLabel = 'Artigos do Blog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        fn(string $operation, $state, Forms\Set $set) =>
                        $operation === 'create' ? $set('slug', Str::slug($state)) : null
                    )
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')
                    ->label('Endereço (slug)')
                    ->prefix('/blog/')
                    ->required()
                    ->unique(Artigo::class, 'slug', ignoreRecord: true)
                    ->helperText('Evite alterar depois de publicado: o endereço antigo deixa de existir no Google.'),

                Forms\Components\DateTimePicker::make('publicado_em')
                    ->label('Publicar em')
                    ->default(now())
                    ->seconds(false)
                    ->helperText('Uma data futura agenda a publicação.'),

                Forms\Components\Toggle::make('ativo')
                    ->label('Ativo')
                    ->default(true),

                Forms\Components\Textarea::make('resumo')
                    ->label('Resumo')
                    ->required()
                    ->rows(2)
                    ->maxLength(300)
                    ->helperText('Aparece na lista de artigos e no início do texto.')
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('conteudo')
                    ->label('Texto do artigo')
                    ->required()
                    ->toolbarButtons(['h2', 'h3', 'bold', 'italic', 'link', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo'])
                    ->helperText('Use subtítulos (H2) para cada parte. Artigos com 600 palavras ou mais, que respondem uma dúvida real, tendem a se posicionar melhor.')
                    ->columnSpanFull(),

                Forms\Components\Section::make('Google')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('titulo_seo')
                            ->label('Título para o Google')
                            ->maxLength(70)
                            ->helperText('Até 70 caracteres. Em branco, usa o título do artigo.'),

                        Forms\Components\Textarea::make('meta_descricao')
                            ->label('Descrição para o Google')
                            ->maxLength(160)
                            ->rows(2)
                            ->helperText('Até 160 caracteres. Em branco, usa o resumo.'),

                        Forms\Components\Repeater::make('faq')
                            ->label('Perguntas frequentes')
                            ->schema([
                                Forms\Components\TextInput::make('pergunta')->required(),
                                Forms\Components\Textarea::make('resposta')->required()->rows(3),
                            ])
                            ->itemLabel(fn(array $state): ?string => $state['pergunta'] ?? null)
                            ->addActionLabel('Adicionar pergunta')
                            ->collapsible()
                            ->defaultItems(0),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('publicado_em', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('publicado_em')
                    ->label('Publicação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver no site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(Artigo $record) => route('site.artigo', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn(Artigo $record) => $record->estaPublicado()),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListArtigos::route('/'),
            'create' => Pages\CreateArtigo::route('/create'),
            'edit' => Pages\EditArtigo::route('/{record}/edit'),
        ];
    }
}
