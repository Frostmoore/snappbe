<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProvincialSectionResource\Pages;
use App\Models\ProvincialSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProvincialSectionResource extends Resource
{
    protected static ?string $model = ProvincialSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Sezioni provinciali';

    protected static ?string $modelLabel = 'sezione provinciale';

    protected static ?string $pluralModelLabel = 'sezioni provinciali';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\TextInput::make('province')->label('Provincia')->maxLength(255),
            Forms\Components\TextInput::make('region')->label('Regione')->maxLength(255),
            Forms\Components\RichEditor::make('body')
                ->label('Contenuto')
                ->helperText('Testo libero formattato: mostrato (grigio scuro) quando si espande la card nell\'app.')
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
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('province')->label('Provincia')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('region')->label('Regione')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefono')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Attiva')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('province')
                    ->label('Provincia')
                    ->options(fn () => ProvincialSection::query()->whereNotNull('province')->distinct()->pluck('province', 'province')),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProvincialSections::route('/'),
            'create' => Pages\CreateProvincialSection::route('/create'),
            'edit' => Pages\EditProvincialSection::route('/{record}/edit'),
        ];
    }
}
