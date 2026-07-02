<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Documenti';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documenti';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Titolo')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Textarea::make('description')->label('Descrizione')->rows(2)->columnSpanFull(),
            Forms\Components\FileUpload::make('file_path')
                ->label('File')
                ->disk('public')->directory('documents')->visibility('public')
                ->downloadable()
                ->maxSize(20480) // 20 MB
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Attivo')->default(true),
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
                Tables\Columns\IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
