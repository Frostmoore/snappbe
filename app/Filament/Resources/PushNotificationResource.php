<?php

namespace App\Filament\Resources;

use App\Enums\PushTarget;
use App\Filament\Resources\PushNotificationResource\Pages;
use App\Models\AccessLevel;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\PushNotificationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PushNotificationResource extends Resource
{
    protected static ?string $model = PushNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Comunicazioni';

    protected static ?string $navigationLabel = 'Notifiche push';

    protected static ?string $modelLabel = 'notifica';

    protected static ?string $pluralModelLabel = 'notifiche';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Contenuto')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Titolo')->required()->maxLength(255),
                    Forms\Components\Textarea::make('body')
                        ->label('Testo')->required()->rows(3)->columnSpanFull(),
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Immagine (carica)')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('notifications')
                        ->visibility('public')
                        ->maxSize(4096),
                    Forms\Components\TextInput::make('image_url')
                        ->label('Oppure immagine (URL esterno)')
                        ->url()->maxLength(1024)
                        ->helperText('Usato solo se non carichi un\'immagine.'),
                    Forms\Components\TextInput::make('deep_link')
                        ->label('Link al tap (facoltativo)')
                        ->placeholder('/articles/123  oppure  https://...')
                        ->helperText('Schermata in-app (es: /articles/{id} · /newsletters · /partners · /events/{id} · /magazine) oppure un link esterno https:// (apre il browser).')
                        ->maxLength(1024),
                    Forms\Components\KeyValue::make('data')
                        ->label('Dati extra (facoltativi)')
                        ->keyLabel('Chiave')->valueLabel('Valore')
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Destinatari')
                ->schema([
                    Forms\Components\Select::make('target')
                        ->label('Invia a')
                        ->options(PushTarget::options())
                        ->default(PushTarget::All->value)
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('target_level')
                        ->label('Livello minimo')
                        ->options(fn () => AccessLevel::query()->orderBy('weight')->pluck('label', 'key'))
                        ->helperText('Raggiunge questo livello e superiori.')
                        ->visible(fn (Get $get) => $get('target') === PushTarget::Level->value)
                        ->required(fn (Get $get) => $get('target') === PushTarget::Level->value),
                    Forms\Components\Select::make('target_role')
                        ->label('Ruolo WordPress')
                        ->options(fn () => User::query()
                            ->whereNotNull('wp_role')
                            ->orderBy('wp_role_label')
                            ->distinct()
                            ->pluck('wp_role_label', 'wp_role'))
                        ->searchable()
                        ->helperText('Invia agli utenti con questo ruolo del sito.')
                        ->visible(fn (Get $get) => $get('target') === PushTarget::Role->value)
                        ->required(fn (Get $get) => $get('target') === PushTarget::Role->value),
                    Forms\Components\Select::make('target_user_ids')
                        ->label('Utenti')
                        ->multiple()->searchable()
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                        ->visible(fn (Get $get) => $get('target') === PushTarget::Users->value)
                        ->required(fn (Get $get) => $get('target') === PushTarget::Users->value)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Titolo')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('target')
                    ->label('Destinatari')->badge()
                    ->formatStateUsing(fn (PushTarget $state) => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'sent' => 'success',
                        'queued' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'sent' => 'Inviata',
                        'queued' => 'In invio',
                        default => 'Bozza',
                    }),
                Tables\Columns\TextColumn::make('sent_at')->label('Inviata il')->dateTime('d/m/Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('stats.success')->label('Successi')->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Invia')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Inviare la notifica?')
                    ->modalDescription('L\'invio verrà messo in coda ed eseguito in background.')
                    ->visible(fn (PushNotification $record) => ! in_array($record->status, ['queued', 'sent'], true))
                    ->action(function (PushNotification $record) {
                        app(PushNotificationService::class)->queue($record);

                        Notification::make()
                            ->title('Notifica accodata')
                            ->body('L\'invio è in corso in background. Lo stato passerà a "Inviata" al termine.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushNotifications::route('/'),
            'create' => Pages\CreatePushNotification::route('/create'),
            'edit' => Pages\EditPushNotification::route('/{record}/edit'),
        ];
    }
}
