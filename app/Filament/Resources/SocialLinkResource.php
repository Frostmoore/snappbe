<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SocialLinkResource\Pages;
use App\Models\SocialLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SocialLinkResource extends Resource
{
    protected static ?string $model = SocialLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Social links';

    protected static ?string $modelLabel = 'social link';

    protected static ?string $pluralModelLabel = 'social links';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('platform')
                ->label('Piattaforma')
                ->options([
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'linkedin' => 'LinkedIn',
                    'youtube' => 'YouTube',
                    'x' => 'X (Twitter)',
                    'tiktok' => 'TikTok',
                    'telegram' => 'Telegram',
                    'whatsapp' => 'WhatsApp',
                    'website' => 'Sito web',
                ])
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('label')->label('Etichetta')->maxLength(255),
            Forms\Components\TextInput::make('url')->label('URL')->url()->required()->maxLength(1024),
            Forms\Components\FileUpload::make('icon_path')
                ->label('Icona (PNG o SVG)')
                ->disk('public')->directory('social')->visibility('public')
                ->acceptedFileTypes(['image/png', 'image/svg+xml', 'image/jpeg'])
                ->maxSize(1024)
                ->helperText('Se vuota, viene usata l\'icona del brand in base alla piattaforma.'),
            Forms\Components\ColorPicker::make('background_color')->label('Colore sfondo'),
            Forms\Components\ColorPicker::make('icon_color')
                ->label('Colore icona (solo SVG)')
                ->helperText('Applicato solo se l\'icona è un SVG.'),
            Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Attivo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('platform')->label('Piattaforma')->badge(),
                Tables\Columns\TextColumn::make('label')->label('Etichetta')->placeholder('—'),
                Tables\Columns\TextColumn::make('url')->label('URL')->limit(40)->url(fn (SocialLink $r) => $r->url, true),
                Tables\Columns\IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSocialLinks::route('/'),
            'create' => Pages\CreateSocialLink::route('/create'),
            'edit' => Pages\EditSocialLink::route('/{record}/edit'),
        ];
    }
}
