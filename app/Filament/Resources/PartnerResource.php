<?php

namespace App\Filament\Resources;

use App\Enums\PartnerType;
use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Convenzioni & Partners';

    protected static ?string $modelLabel = 'partner';

    protected static ?string $pluralModelLabel = 'convenzioni & partners';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
            Forms\Components\Select::make('type')->label('Tipo')->options(PartnerType::options())->default(PartnerType::Partner->value)->required(),
            Forms\Components\FileUpload::make('logo_path')->label('Logo')->image()->disk('public')->directory('partners')->visibility('public')->maxSize(4096),
            Forms\Components\TextInput::make('url')->label('Link')->url()->maxLength(1024),
            Forms\Components\Textarea::make('description')->label('Descrizione')->columnSpanFull(),
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
                Tables\Columns\ImageColumn::make('logo_path')->label('Logo')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge()->formatStateUsing(fn (PartnerType $state) => $state->label()),
                Tables\Columns\TextColumn::make('url')->label('Link')->limit(35)->url(fn (Partner $r) => $r->url, true)->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Tipo')->options(PartnerType::options()),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
