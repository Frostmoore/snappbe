<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\ManageAppSettings;
use App\Filament\Resources\EventResource;
use App\Filament\Resources\MagazineIssueResource;
use App\Filament\Resources\OrgChartMemberResource;
use App\Filament\Resources\PartnerResource;
use App\Filament\Resources\PostResource;
use App\Filament\Resources\ProvincialSectionResource;
use App\Filament\Resources\PushNotificationResource;
use App\Filament\Resources\SocialLinkResource;
use App\Models\Event;
use App\Models\MagazineIssue;
use App\Models\OrgChartMember;
use App\Models\Partner;
use App\Models\Post;
use App\Models\ProvincialSection;
use App\Models\PushNotification;
use App\Models\SocialLink;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Pannelloni in dashboard: card native Filament, cliccabili, verso ogni sezione.
 * Posizionato sotto i widget di default (Benvenuto/Filament).
 */
class SectionsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Impostazioni app', 'Apri')
                ->description('Header, logo, nome, colore')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('gray')
                ->url(ManageAppSettings::getUrl()),

            Stat::make('Social links', SocialLink::count())
                ->description('Link ai social')
                ->descriptionIcon('heroicon-m-share')
                ->color('info')
                ->url(SocialLinkResource::getUrl()),

            Stat::make('Sezioni provinciali', ProvincialSection::count())
                ->description('Elenco con ricerca e filtro')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('success')
                ->url(ProvincialSectionResource::getUrl()),

            Stat::make('Convenzioni & Partners', Partner::count())
                ->description('Loghi cliccabili con link')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning')
                ->url(PartnerResource::getUrl()),

            Stat::make("L'Agente di Assicurazione", MagazineIssue::count())
                ->description('Numeri della rivista')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary')
                ->url(MagazineIssueResource::getUrl()),

            Stat::make('Organigramma', OrgChartMember::count())
                ->description('Struttura ad albero')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->url(OrgChartMemberResource::getUrl()),

            Stat::make('Eventi', Event::count())
                ->description('Con registrazione')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success')
                ->url(EventResource::getUrl()),

            Stat::make('Post in-app', Post::count())
                ->description('Contenuti nativi')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('primary')
                ->url(PostResource::getUrl()),

            Stat::make('Notifiche push', PushNotification::count())
                ->description('Componi e invia')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color('danger')
                ->url(PushNotificationResource::getUrl()),
        ];
    }
}
