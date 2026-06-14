<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservedElementResource\Pages;
use App\Models\ReservedElement;
use App\Models\ReservedSection;
use App\Support\WpRoles;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReservedElementResource extends Resource
{
    protected static ?string $model = ReservedElement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationGroup = 'Area riservata';

    protected static ?string $navigationLabel = 'Elementi';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'elemento';

    protected static ?string $pluralModelLabel = 'elementi';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('reserved_section_id')
                ->label('Sezione')
                ->options(fn () => ReservedSection::with('tile')->get()->mapWithKeys(
                    fn (ReservedSection $s) => [$s->id => ($s->tile?->title ?? '?') . ' › ' . $s->title]
                ))
                ->required()->searchable(),
            Forms\Components\TextInput::make('title')->label('Titolo')->required()->maxLength(255),
            Forms\Components\Textarea::make('description')->label('Descrizione')->columnSpanFull(),
            Forms\Components\FileUpload::make('file_path')->label('File da scaricare')
                ->disk('public')->directory('reserved/files')->visibility('public')->maxSize(20480)
                ->helperText('Carica un file (PDF, doc, immagine…) oppure usa il link esterno.'),
            Forms\Components\TextInput::make('external_url')->label('Link esterno (alternativa al file)')->url()->maxLength(1024),
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
                Tables\Columns\TextColumn::make('section.tile.title')->label('Tile')->badge(),
                Tables\Columns\TextColumn::make('section.title')->label('Sezione'),
                Tables\Columns\TextColumn::make('title')->label('Elemento')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('file_path')->label('File')->boolean()->trueIcon('heroicon-o-paper-clip')->falseIcon('heroicon-o-link'),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordine')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reserved_section_id')->label('Sezione')->relationship('section', 'title'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservedElements::route('/'),
            'create' => Pages\CreateReservedElement::route('/create'),
            'edit' => Pages\EditReservedElement::route('/{record}/edit'),
        ];
    }
}
