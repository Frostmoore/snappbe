<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Utenti';

    protected static ?string $modelLabel = 'utente';

    protected static ?string $pluralModelLabel = 'utenti';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->label('Ruolo')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label()]))
                    ->default(UserRole::Member->value)
                    ->required(),
                Forms\Components\TextInput::make('membership_level')
                    ->label('Livello iscrizione (da WP)')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Ereditato dal sito WordPress dopo il collegamento account (Fase 6).'),
                // Password opzionale: valorizzata solo se compilata (non sovrascrive se vuota).
                Forms\Components\TextInput::make('password')
                    ->label('Nuova password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->maxLength(255),
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Ruolo')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state) => $state->label())
                    ->color(fn (UserRole $state) => $state === UserRole::SuperAdmin ? 'danger' : ($state->isStaffOrAbove() ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('membership_level')
                    ->label('Livello')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verificato')
                    ->boolean(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Social')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrato')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label()])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
