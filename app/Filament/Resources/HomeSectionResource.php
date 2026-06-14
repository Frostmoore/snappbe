<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeSectionResource\Pages;
use App\Models\HomeSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Sezioni Home';

    protected static ?string $modelLabel = 'sezione home';

    protected static ?string $pluralModelLabel = 'sezioni home';

    /** Destinazioni disponibili (rotte dell'app). */
    public static function routeOptions(): array
    {
        return [
            '/newsletters' => 'Newsletter',
            '/articles' => 'Articoli',
            '/provincial' => 'Sezioni provinciali',
            '/events' => 'Eventi',
            '/magazine' => "L'Agente di Assicurazione",
            '/orgchart' => 'Organigramma',
            '/partners' => 'Convenzioni & Partners',
            '/posts' => 'Comunicazioni',
            '/account' => 'Area riservata',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Contenuto')->schema([
                Forms\Components\TextInput::make('title')->label('Titolo')->required()->maxLength(255),
                Forms\Components\TextInput::make('subtitle')->label('Sottotitolo')->maxLength(255),
                Forms\Components\Select::make('route')->label('Destinazione')->options(self::routeOptions())->required(),
                Forms\Components\Select::make('layout')->label('Larghezza')
                    ->options(['wide' => 'Tutta pagina', 'half' => 'Mezza pagina'])
                    ->default('half')->required(),
            ])->columns(2),

            Forms\Components\Section::make('Aspetto')->schema([
                Forms\Components\FileUpload::make('icon_path')
                    ->label('Icona (PNG o SVG)')
                    ->disk('public')->directory('sections')->visibility('public')
                    ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg'])
                    ->maxSize(1024)
                    ->helperText('Se vuota, viene usata l\'icona di default della sezione.'),
                Forms\Components\ColorPicker::make('background_color')->label('Colore sfondo'),
                Forms\Components\ColorPicker::make('icon_color')->label('Colore icona (solo SVG)'),
                Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Attiva')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('icon_path')->label('Icona')->disk('public')->height(32),
                Tables\Columns\TextColumn::make('title')->label('Titolo')->searchable(),
                Tables\Columns\TextColumn::make('layout')->label('Larghezza')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'wide' ? 'Tutta pagina' : 'Mezza pagina'),
                Tables\Columns\TextColumn::make('route')->label('Destinazione'),
                Tables\Columns\IconColumn::make('is_active')->label('Attiva')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeSections::route('/'),
            'create' => Pages\CreateHomeSection::route('/create'),
            'edit' => Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }
}
