<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MagazineIssueResource\Pages;
use App\Models\MagazineIssue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MagazineIssueResource extends Resource
{
    protected static ?string $model = MagazineIssue::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = "L'Agente di Assicurazione";

    protected static ?string $modelLabel = 'numero';

    protected static ?string $pluralModelLabel = 'numeri rivista';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Titolo')->required()->maxLength(255),
            Forms\Components\TextInput::make('number')->label('Numero')->numeric(),
            Forms\Components\TextInput::make('url')->label('Link al numero online')->url()->required()->maxLength(1024),
            Forms\Components\DatePicker::make('issue_date')->label('Data'),
            Forms\Components\FileUpload::make('cover_path')->label('Copertina')->image()->disk('public')->directory('magazine')->visibility('public')->maxSize(4096),
            Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Attivo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')->label('Copertina')->disk('public')->height(50),
                Tables\Columns\TextColumn::make('title')->label('Titolo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('number')->label('N.')->sortable(),
                Tables\Columns\TextColumn::make('issue_date')->label('Data')->date('d/m/Y')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMagazineIssues::route('/'),
            'create' => Pages\CreateMagazineIssue::route('/create'),
            'edit' => Pages\EditMagazineIssue::route('/{record}/edit'),
        ];
    }
}
