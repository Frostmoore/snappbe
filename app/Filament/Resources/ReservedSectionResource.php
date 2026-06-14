<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservedSectionResource\Pages;
use App\Models\ReservedSection;
use App\Support\WpRoles;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReservedSectionResource extends Resource
{
    protected static ?string $model = ReservedSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static ?string $navigationGroup = 'Area riservata';

    protected static ?string $navigationLabel = 'Sezioni';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'sezione';

    protected static ?string $pluralModelLabel = 'sezioni';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('reserved_tile_id')->label('Tile')->relationship('tile', 'title')->required()->searchable()->preload(),
            Forms\Components\TextInput::make('title')->label('Titolo sezione')->required()->maxLength(255),
            Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
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
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('tile.title')->label('Tile')->sortable()->badge(),
                Tables\Columns\TextColumn::make('title')->label('Sezione')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('elements_count')->counts('elements')->label('Elementi'),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordine')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reserved_tile_id')->label('Tile')->relationship('tile', 'title'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservedSections::route('/'),
            'create' => Pages\CreateReservedSection::route('/create'),
            'edit' => Pages\EditReservedSection::route('/{record}/edit'),
        ];
    }
}
