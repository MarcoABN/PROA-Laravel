<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    // Configuração do Menu
    protected static ?string $navigationLabel = 'Usuários do Sistema';
    protected static ?string $modelLabel = 'Usuário';
    protected static ?string $navigationGroup = 'Painel de Controle'; // Agrupa no menu
    protected static ?int $navigationSort = 1; // Ordem dentro do grupo

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Usuário')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true) // Permite manter o email ao editar
                            ->maxLength(255),

                        // --- LÓGICA DE SENHA ---
                        Forms\Components\TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            // Se estiver criando, é obrigatório. Se editando, é opcional.
                            ->required(fn(string $operation): bool => $operation === 'create')
                            // Só criptografa e salva se o campo for preenchido
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->confirmed() // Exige campo de confirmação
                            ->rule(
                                Password::min(8) // Mínimo 8 caracteres
                                    ->mixedCase()       // Maiúsculas e Minúsculas
                                    ->letters()         // Pelo menos uma letra
                                    ->numbers()         // Pelo menos um número
                                    ->symbols()         // Pelo menos um caractere especial (@, #, $, etc)
                            )
                            ->validationMessages([
                                'confirmed' => 'A confirmação da senha não confere.',
                                'min' => 'A senha deve ter no mínimo :min caracteres.',
                                'password.mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
                                'password.letters' => 'A senha deve conter pelo menos uma letra.',
                                'password.numbers' => 'A senha deve conter pelo menos um número.',
                                'password.symbols' => 'A senha deve conter pelo menos um símbolo (@, #, $, etc).',
                            ])
                            ->helperText(fn(string $operation) => $operation === 'edit' ? 'Deixe em branco para manter a senha atual.' : ''),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Senha')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(false) // Não salva esse campo no banco
                        //->visible(fn(Forms\Get $get) => filled($get('password'))),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                // --- MOSTRAR ÚLTIMO ACESSO ---
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Último Acesso')
                    ->dateTime('d/m/Y H:i') // Formato Brasileiro
                    ->sortable()
                    ->placeholder('Nunca acessou'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar / Senha'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('email', '!=', 'marcoanunes23@gmail.com');
        // Ou use uma lógica mais abrangente, como:
        // ->where('is_invisible', false);
    }
}