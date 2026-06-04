<?php

namespace App\Filament\Resources;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Filament\Resources\PostResource\Pages;
use App\Models\AccessLevel;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Contenuti';

    protected static ?string $navigationLabel = 'Post in-app';

    protected static ?string $modelLabel = 'post';

    protected static ?string $pluralModelLabel = 'post';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contenuto')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titolo')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Identificativo URL univoco; generato dal titolo.'),
                        Forms\Components\Textarea::make('excerpt')
                            ->label('Estratto')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('body')
                            ->label('Corpo')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pubblicazione & accesso')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options(PostType::options())
                            ->default(PostType::Generic->value)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Stato')
                            ->options(PostStatus::options())
                            ->default(PostStatus::Draft->value)
                            ->required(),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Data pubblicazione'),
                        Forms\Components\Select::make('min_level')
                            ->label('Livello minimo')
                            ->options(fn () => AccessLevel::query()->orderBy('weight')->pluck('label', 'key'))
                            ->placeholder('Pubblico (tutti)')
                            ->helperText('Vuoto = visibile a tutti. Altrimenti richiede il livello indicato o superiore.'),
                        Forms\Components\FileUpload::make('cover_path')
                            ->label('Copertina (carica immagine)')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('posts')
                            ->visibility('public')
                            ->maxSize(4096),
                        Forms\Components\TextInput::make('cover_url')
                            ->label('Oppure URL copertina esterno')
                            ->url()
                            ->maxLength(1024)
                            ->helperText('Usato solo se non carichi un\'immagine.'),
                        Forms\Components\TextInput::make('external_url')
                            ->label('URL esterno (es. form evento WP)')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\Select::make('author_id')
                            ->label('Autore')
                            ->relationship('author', 'name')
                            ->default(fn () => auth()->id())
                            ->searchable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (PostType $state) => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (PostStatus $state) => match ($state) {
                        PostStatus::Published => 'success',
                        PostStatus::Draft => 'gray',
                        PostStatus::Archived => 'warning',
                    })
                    ->formatStateUsing(fn (PostStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('min_level')
                    ->label('Livello')
                    ->badge()
                    ->placeholder('Pubblico'),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Pubblicato il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autore')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(PostStatus::options()),
                Tables\Filters\SelectFilter::make('type')->options(PostType::options()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
