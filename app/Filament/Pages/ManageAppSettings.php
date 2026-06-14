<?php

namespace App\Filament\Pages;

use App\Models\AppSettings;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Pagina di impostazioni globali dell'app (singleton): header, logo, nome, colore.
 */
class ManageAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Contenuti app';

    protected static ?string $navigationLabel = 'Impostazioni app';

    protected static ?string $title = 'Impostazioni app';

    protected static string $view = 'filament.pages.manage-app-settings';

    /** @var array<string,mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            AppSettings::current()->only(['app_name', 'header_image_path', 'header_video_path', 'logo_path', 'primary_color'])
        );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identità')
                    ->schema([
                        TextInput::make('app_name')->label('Nome app')->maxLength(255),
                        ColorPicker::make('primary_color')->label('Colore primario'),
                    ])->columns(2),
                Section::make('Header')
                    ->schema([
                        FileUpload::make('logo_path')->label('Logo')
                            ->image()->disk('public')->directory('app')->visibility('public')->maxSize(4096),
                        FileUpload::make('header_image_path')->label('Immagine header (fallback)')
                            ->image()->disk('public')->directory('app')->visibility('public')->maxSize(8192)
                            ->helperText('Mostrata se non c\'è il video o mentre carica.'),
                        FileUpload::make('header_video_path')->label('Video header (opzionale)')
                            ->disk('public')->directory('app')->visibility('public')
                            ->acceptedFileTypes(['video/mp4', 'video/webm'])
                            ->maxSize(51200)
                            ->helperText('MP4/WebM. Riprodotto in loop e muto; l\'immagine sopra è il fallback.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        AppSettings::current()->update($this->form->getState());

        Notification::make()->title('Impostazioni salvate')->success()->send();
    }
}
