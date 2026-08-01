<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestadorResource\Pages;
use App\Models\Prestador;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PrestadorResource extends Resource
{
    protected static ?string $model = Prestador::class;

    protected static ?string $navigationGroup = 'Cadastros Auxiliares';

    protected static ?string $modelLabel = 'Instrutores / Procuradores';
    protected static ?string $pluralModelLabel = 'Instrutores / Procuradores';
    protected static ?string $navigationLabel = 'Instrutores / Procuradores';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- SEÇÃO 1: DADOS PESSOAIS ---
                Forms\Components\Section::make('Dados Pessoais')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label(fn(Get $get) => static::ehPessoaJuridica($get('cpfcnpj')) ? 'Razão Social' : 'Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cpfcnpj')
                            ->label('CPF/CNPJ')
                            ->helperText('Pessoa física (CPF) ou jurídica (CNPJ) — a máscara se ajusta sozinha.')
                            ->required()
                            // Máscara dinâmica: alterna para CNPJ se passar de 14 caracteres digitados
                            ->mask(RawJs::make(<<<'JS'
        $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
    JS))
                            // Remove os caracteres de formatação antes de validar e salvar no banco
                            ->stripCharacters(['.', '-', '/'])
                            ->maxLength(18)
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'CPF/CNPJ já cadastrado',
                                'required' => 'Campo Obrigatório',
                            ])
                            // Alterna os campos exclusivos de pessoa física ao sair do campo
                            ->live(onBlur: true)
                            // Normaliza registros antigos salvos com formato irregular ao abrir a edição
                            ->formatStateUsing(fn(?string $state) => static::formatarDocumento($state)),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),

                        // Grupo de RG — exclusivo de pessoa física
                        Forms\Components\Fieldset::make('Documento de Identidade (RG)')
                            ->schema([
                                Forms\Components\TextInput::make('rg')
                                    ->label('Número RG')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('org_emissor')
                                    ->label('Org. Emissor'),
                                Forms\Components\DatePicker::make('dt_emissao')
                                    ->label('Data de Emissão')
                                    ->displayFormat('d/m/Y') // <--- Visual (Dia/Mês/Ano)
                                    ->format('Y-m-d')        // <--- Banco (Ano-Mês-Dia)
                            ])
                            ->columns(3)
                            ->hidden(fn(Get $get) => static::ehPessoaJuridica($get('cpfcnpj'))),

                        // Outros Dados Civis — exclusivos de pessoa física
                        Forms\Components\TextInput::make('nacionalidade')
                            ->hidden(fn(Get $get) => static::ehPessoaJuridica($get('cpfcnpj'))),
                        Forms\Components\TextInput::make('estado_civil')
                            ->hidden(fn(Get $get) => static::ehPessoaJuridica($get('cpfcnpj'))),
                        Forms\Components\TextInput::make('profissao')
                            ->hidden(fn(Get $get) => static::ehPessoaJuridica($get('cpfcnpj'))),

                        // Contatos
                        Forms\Components\TextInput::make('telefone')
                            ->label('Telefone Fixo')
                            ->mask('(99) 9999-9999'),
                        Forms\Components\TextInput::make('celular')
                            ->label('Celular / WhatsApp')
                            ->mask('(99) 99999-9999'),
                    ]),

                // --- SEÇÃO 2: ENDEREÇO ---
                Forms\Components\Section::make('Endereço')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999'),

                        Forms\Components\TextInput::make('logradouro')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('numero')
                            ->label('Número'),

                        Forms\Components\TextInput::make('complemento'),
                        Forms\Components\TextInput::make('bairro'),
                        Forms\Components\TextInput::make('cidade'),
                        Forms\Components\TextInput::make('uf')
                            ->label('UF')
                            ->maxLength(2),
                    ])->collapsible(),

                // --- SEÇÃO 3: DADOS DA HABILITAÇÃO (CHA) ---
                // A CHA é um documento pessoal do amador, então não se aplica a pessoa jurídica.
                Forms\Components\Section::make('Dados da Habilitação (CHA)')
                    ->description('Necessário para emissão de Atestados (Anexos 3B e 5E).')
                    ->columns(2)
                    ->hidden(fn(Get $get) => static::ehPessoaJuridica($get('cpfcnpj')))
                    ->schema([
                        Forms\Components\TextInput::make('cha_numero')
                            ->label('Número da CHA')
                            ->maxLength(20),

                        Forms\Components\Select::make('cha_categoria')
                            ->label('Categoria')
                            ->options([
                                'ARA' => 'Arrais-Amador (ARA)',
                                'MTA' => 'Motonauta (MTA)',
                                'ARA-MTA' => 'Arrais e Motonauta (ARA-MTA)',
                                'MSA' => 'Mestre-Amador (MSA)',
                                'CPA' => 'Capitão-Amador (CPA)',
                                'VELEIRO' => 'Veleiro',
                            ])
                            ->searchable(),

                        Forms\Components\DatePicker::make('cha_dtemissao')
                            ->label('Data de Emissão CHA')
                            ->displayFormat('d/m/Y'),
                    ]),

                // --- SEÇÃO 4: FUNÇÕES E VÍNCULOS ---
                Forms\Components\Section::make('Funções do Prestador')
                    ->description('Defina se este cadastro pode ser usado como Instrutor ou Procurador no sistema.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_instrutor')
                            ->label('É Instrutor?')
                            ->default(false)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_procurador')
                            ->label('É Procurador?')
                            ->default(false)
                            ->live(), // Recarrega o formulário para mostrar o tipo

                        Forms\Components\Select::make('tipo_procuracao')
                            ->label('Tipo de Procuração')
                            ->options([
                                'COMPLETO' => 'Completo',
                                'REDUZIDO' => 'Reduzido'
                            ])
                            ->visible(fn(Get $get) => $get('is_procurador')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cpfcnpj')
                    ->label('Documento')
                    ->formatStateUsing(fn(?string $state) => static::formatarDocumento($state))
                    ->searchable(query: function (Builder $query, string $search) {
                        $numeros = preg_replace('/[^0-9]/', '', $search);
                        if (!empty($numeros)) {
                            $query->whereRaw("REGEXP_REPLACE(cpfcnpj, '[^0-9]', '', 'g') LIKE ?", ["%{$numeros}%"]);
                        }
                    }),
                Tables\Columns\TextColumn::make('celular')
                    ->label('Contato'),
                Tables\Columns\IconColumn::make('is_instrutor')
                    ->boolean()
                    ->label('Instrutor'),
                Tables\Columns\IconColumn::make('is_procurador')
                    ->boolean()
                    ->label('Procurador'),
                Tables\Columns\TextColumn::make('cidade')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('instrutores')
                    ->query(fn($query) => $query->where('is_instrutor', true))
                    ->label('Apenas Instrutores'),
                Tables\Filters\Filter::make('procuradores')
                    ->query(fn($query) => $query->where('is_procurador', true))
                    ->label('Apenas Procuradores'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Um documento com 14 dígitos é CNPJ, ou seja, pessoa jurídica.
     * Usado para esconder no formulário os campos que só existem para pessoa física.
     */
    protected static function ehPessoaJuridica(?string $documento): bool
    {
        return strlen(preg_replace('/[^0-9]/', '', (string) $documento)) === 14;
    }

    /**
     * Aplica a máscara de CPF (11 dígitos) ou CNPJ (14 dígitos) a partir dos dígitos puros.
     * Valores com quantidade inesperada de dígitos são devolvidos como estão.
     */
    protected static function formatarDocumento(?string $state): ?string
    {
        if (!$state) {
            return null;
        }

        $digitos = preg_replace('/[^0-9]/', '', $state);

        if (strlen($digitos) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos);
        }
        if (strlen($digitos) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digitos);
        }

        return $state;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrestadors::route('/'),
            'create' => Pages\CreatePrestador::route('/create'),
            'edit' => Pages\EditPrestador::route('/{record}/edit'),
        ];
    }
}