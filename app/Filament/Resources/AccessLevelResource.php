<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccessLevelResource\Pages;
use App\Filament\Resources\AccessLevelResource\RelationManagers;
use App\Models\AccessLevel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccessLevelResource extends Resource
{
    protected static ?string $model = AccessLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Gestione';

    protected static ?string $navigationLabel = 'Livelli di accesso';

    protected static ?string $modelLabel = 'livello';

    protected static ?string $pluralModelLabel = 'livelli';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Chiave')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Identificativo tecnico (es. iscritto, premium). Mappato ai livelli WP.'),
                Forms\Components\TextInput::make('label')
                    ->label('Etichetta')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight')
                    ->label('Peso (gerarchia)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Più alto = più privilegiato. Un contenuto richiede peso ≥ a quello indicato.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccessLevels::route('/'),
            'create' => Pages\CreateAccessLevel::route('/create'),
            'edit' => Pages\EditAccessLevel::route('/{record}/edit'),
        ];
    }
}
