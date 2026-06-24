<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrgChartSectionResource\Pages;
use App\Models\OrgChartSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrgChartSectionResource extends Resource
{
    protected static ?string $model = OrgChartSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Organigramma — Sezioni';

    protected static ?string $modelLabel = 'sezione organigramma';

    protected static ?string $pluralModelLabel = 'sezioni organigramma';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('subtitle')
                ->label('Sottotitolo (eyebrow)')
                ->helperText('Mostrato sopra il titolo, in maiuscoletto tra le bande oro (come "LINK SOCIAL").')
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('title')
                ->label('Titolo sezione')
                ->required()->maxLength(255)
                ->helperText('Deve combaciare con la "Sezione" assegnata ai membri (es. Direzione).')
                ->columnSpanFull(),
            Forms\Components\RichEditor::make('description')
                ->label('Descrizione')
                ->helperText('Testo (rich) mostrato sotto il titolo della sezione nell\'app.')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Attiva')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titolo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')->label('Descrizione')->limit(60)->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('Attiva')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrgChartSections::route('/'),
            'create' => Pages\CreateOrgChartSection::route('/create'),
            'edit' => Pages\EditOrgChartSection::route('/{record}/edit'),
        ];
    }
}
