<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrgChartMemberResource\Pages;
use App\Models\OrgChartMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrgChartMemberResource extends Resource
{
    protected static ?string $model = OrgChartMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Organigramma';

    protected static ?string $modelLabel = 'membro';

    protected static ?string $pluralModelLabel = 'organigramma';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('group')
                ->label('Sezione')
                ->helperText('I membri sono raggruppati per sezione. Gestisci titoli/descrizioni in "Organigramma — Sezioni".')
                ->options(fn () => \App\Models\OrgChartSection::query()->orderBy('sort_order')->pluck('title', 'title'))
                ->searchable()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
            Forms\Components\TextInput::make('role')->label('Ruolo')->maxLength(255),
            Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255),
            Forms\Components\FileUpload::make('photo_path')->label('Foto')->image()->avatar()->disk('public')->directory('org')->visibility('public')->maxSize(4096),
            Forms\Components\TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Attivo')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')->label('Foto')->circular()->disk('public'),
                Tables\Columns\TextColumn::make('group')->label('Sezione')->placeholder('— (Altri)')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role')->label('Ruolo')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrgChartMembers::route('/'),
            'create' => Pages\CreateOrgChartMember::route('/create'),
            'edit' => Pages\EditOrgChartMember::route('/{record}/edit'),
        ];
    }
}
