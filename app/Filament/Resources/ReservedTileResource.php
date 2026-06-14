<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservedTileResource\Pages;
use App\Models\ReservedTile;
use App\Support\WpRoles;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReservedTileResource extends Resource
{
    protected static ?string $model = ReservedTile::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Area riservata';

    protected static ?string $navigationLabel = 'Tiles';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'tile';

    protected static ?string $pluralModelLabel = 'tiles';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Titolo')->required()->maxLength(255),
            Forms\Components\TextInput::make('subtitle')->label('Sottotitolo')->maxLength(255),
            Forms\Components\FileUpload::make('icon_path')->label('Icona/Immagine')->image()->disk('public')->directory('reserved')->visibility('public')->maxSize(4096),
            Forms\Components\ColorPicker::make('color')->label('Colore sfondo'),
            Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Attiva')->default(true),
            Forms\Components\CheckboxList::make('visible_roles')
                ->label('Visibile ai ruoli SNA')
                ->helperText('Nessuna selezione = visibile a tutti gli iscritti.')
                ->options(WpRoles::options())
                ->columns(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('icon_path')->label('Icona')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('title')->label('Titolo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subtitle')->label('Sottotitolo')->limit(40)->placeholder('—'),
                Tables\Columns\TextColumn::make('sections_count')->counts('sections')->label('Sezioni'),
                Tables\Columns\IconColumn::make('is_active')->label('Attiva')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservedTiles::route('/'),
            'create' => Pages\CreateReservedTile::route('/create'),
            'edit' => Pages\EditReservedTile::route('/{record}/edit'),
        ];
    }
}
