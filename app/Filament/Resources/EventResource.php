<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Eventi';

    protected static ?string $modelLabel = 'evento';

    protected static ?string $pluralModelLabel = 'eventi';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Evento')->schema([
                Forms\Components\TextInput::make('title')->label('Titolo')->required()->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')->label('Descrizione')->rows(4)->columnSpanFull(),
                Forms\Components\FileUpload::make('cover_path')->label('Copertina')->image()->disk('public')->directory('events')->visibility('public')->maxSize(8192),
            ])->columns(2),
            Forms\Components\Section::make('Quando & dove')->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label('Inizio')->required(),
                Forms\Components\DateTimePicker::make('ends_at')->label('Fine'),
                Forms\Components\TextInput::make('location')->label('Luogo')->maxLength(255),
                Forms\Components\TextInput::make('registration_url')->label('URL registrazione (form su WordPress)')
                    ->url()->maxLength(1024)
                    ->helperText('Aperto in WebView dall\'app; a registrazione avvenuta i dati creano l\'evento sul Google Calendar.'),
                Forms\Components\Toggle::make('is_published')->label('Pubblicato')->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titolo')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('starts_at')->label('Inizio')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('location')->label('Luogo')->placeholder('—')->toggleable(),
                Tables\Columns\IconColumn::make('registration_url')->label('Registrazione')->boolean()->state(fn (Event $r) => filled($r->registration_url)),
                Tables\Columns\IconColumn::make('is_published')->label('Pubblicato')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Pubblicato'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
