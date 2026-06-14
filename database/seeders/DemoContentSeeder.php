<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AppSettings;
use App\Models\Event;
use App\Models\HomeSection;
use App\Models\MagazineIssue;
use App\Models\OrgChartMember;
use App\Models\Partner;
use App\Models\Post;
use App\Models\ProvincialSection;
use App\Models\PushNotification;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Dati demo per tutte le sezioni native dell'app. Idempotente (updateOrCreate).
 * NB: Articoli e Newsletter arrivano da WordPress (proxy), non seedabili qui.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        // Impostazioni app + immagine header (scaricata una volta in storage pubblico)
        $headerPath = 'app/header.jpg';
        if (! Storage::disk('public')->exists($headerPath)) {
            try {
                $bytes = Http::timeout(20)->get('https://picsum.photos/1200/500')->body();
                if (! empty($bytes)) {
                    Storage::disk('public')->put($headerPath, $bytes);
                }
            } catch (\Throwable $e) {
                // offline: header resta vuoto, l'app mostra lo sfondo di default
            }
        }
        AppSettings::current()->update([
            'app_name' => 'SNA',
            'primary_color' => '#0B3D66',
            'header_image_path' => Storage::disk('public')->exists($headerPath) ? $headerPath : null,
        ]);

        // Sezioni Home (card di navigazione)
        $homeSections = [
            ['title' => 'Newsletter', 'subtitle' => 'Le ultime dal sindacato', 'route' => '/newsletters', 'layout' => 'wide', 'background_color' => '#0B3D66'],
            ['title' => 'Articoli', 'subtitle' => null, 'route' => '/articles', 'layout' => 'half', 'background_color' => '#1565C0'],
            ['title' => 'Sezioni provinciali', 'subtitle' => null, 'route' => '/provincial', 'layout' => 'half', 'background_color' => '#00897B'],
            ['title' => 'Eventi', 'subtitle' => 'Convegni, webinar e incontri', 'route' => '/events', 'layout' => 'wide', 'background_color' => '#2E7D32'],
            ['title' => "L'Agente di Assicurazione", 'subtitle' => null, 'route' => '/magazine', 'layout' => 'half', 'background_color' => '#6A1B9A'],
            ['title' => 'Organigramma', 'subtitle' => null, 'route' => '/orgchart', 'layout' => 'half', 'background_color' => '#455A64'],
        ];
        foreach ($homeSections as $i => $hs) {
            HomeSection::updateOrCreate(['route' => $hs['route']], $hs + ['sort_order' => $i, 'is_active' => true]);
        }

        // Social links
        $socials = [
            ['platform' => 'facebook', 'label' => 'Facebook', 'url' => 'https://facebook.com/sna', 'sort_order' => 1],
            ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/sna', 'sort_order' => 2],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/sna', 'sort_order' => 3],
            ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/@sna', 'sort_order' => 4],
        ];
        foreach ($socials as $s) {
            SocialLink::updateOrCreate(['platform' => $s['platform']], $s + ['is_active' => true]);
        }

        // Utente "member" per testare i contenuti riservati (livello premium)
        User::updateOrCreate(
            ['email' => 'member@sna.it'],
            [
                'name' => 'Mario Agente',
                'password' => 'password',
                'role' => UserRole::Member->value,
                'membership_level' => 'premium',
                'email_verified_at' => now(),
            ]
        );

        // Post nativi (con immagini placeholder; alcuni riservati)
        $posts = [
            ['slug' => 'assemblea-2026', 'title' => 'Assemblea Nazionale 2026', 'type' => 'news', 'min_level' => null, 'excerpt' => 'Convocata l\'assemblea nazionale degli agenti.'],
            ['slug' => 'rinnovo-ccnl', 'title' => 'Aggiornamenti sul rinnovo CCNL', 'type' => 'news', 'min_level' => null, 'excerpt' => 'Le ultime novità sul contratto.'],
            ['slug' => 'tool-loghi', 'title' => 'Nuovi loghi e materiali social', 'type' => 'tool', 'min_level' => 'iscritto', 'excerpt' => 'Scarica i materiali aggiornati (riservato iscritti).'],
            ['slug' => 'guida-premium', 'title' => 'Guida operativa avanzata', 'type' => 'generic', 'min_level' => 'premium', 'excerpt' => 'Contenuto riservato al livello premium.'],
        ];
        foreach ($posts as $i => $p) {
            Post::updateOrCreate(['slug' => $p['slug']], [
                'title' => $p['title'],
                'type' => $p['type'],
                'excerpt' => $p['excerpt'],
                'body' => '<p>Contenuto di esempio per "' . $p['title'] . '". '
                    . 'Questo testo è un segnaposto per la demo dei contenuti in-app.</p>',
                'cover_url' => 'https://picsum.photos/seed/snapp' . $i . '/800/400',
                'status' => 'published',
                'published_at' => now()->subDays($i),
                'min_level' => $p['min_level'],
            ]);
        }

        // Sezioni provinciali
        $sections = [
            ['name' => 'Sezione di Milano', 'province' => 'MI', 'region' => 'Lombardia', 'address' => 'Via Roma 1, Milano', 'email' => 'milano@sna.it', 'phone' => '0212345678', 'website' => 'https://sna.it/milano'],
            ['name' => 'Sezione di Roma', 'province' => 'RM', 'region' => 'Lazio', 'address' => 'Via Nazionale 10, Roma', 'email' => 'roma@sna.it', 'phone' => '0698765432', 'website' => 'https://sna.it/roma'],
            ['name' => 'Sezione di Napoli', 'province' => 'NA', 'region' => 'Campania', 'address' => 'Corso Umberto 5, Napoli', 'email' => 'napoli@sna.it', 'phone' => '0811122334'],
            ['name' => 'Sezione di Torino', 'province' => 'TO', 'region' => 'Piemonte', 'address' => 'Via Po 20, Torino', 'email' => 'torino@sna.it'],
        ];
        foreach ($sections as $i => $s) {
            ProvincialSection::updateOrCreate(['name' => $s['name']], $s + ['sort_order' => $i, 'is_active' => true]);
        }

        // Convenzioni & Partners
        $partners = [
            ['name' => 'Banca Assicura', 'type' => 'convenzione', 'url' => 'https://example.com/banca'],
            ['name' => 'Auto Service', 'type' => 'convenzione', 'url' => 'https://example.com/auto'],
            ['name' => 'TechPartner', 'type' => 'partner', 'url' => 'https://example.com/tech'],
            ['name' => 'Formazione Pro', 'type' => 'partner', 'url' => 'https://example.com/formazione'],
        ];
        foreach ($partners as $i => $p) {
            Partner::updateOrCreate(['name' => $p['name']], $p + ['sort_order' => $i, 'is_active' => true]);
        }

        // Rivista L'Agente di Assicurazione
        for ($n = 1; $n <= 4; $n++) {
            MagazineIssue::updateOrCreate(['title' => "Numero $n / 2026"], [
                'number' => $n,
                'url' => "https://sna.it/rivista/$n",
                'issue_date' => Carbon::create(2026, $n * 2, 1),
                'sort_order' => $n,
                'is_active' => true,
            ]);
        }

        // Organigramma (albero)
        $presidente = OrgChartMember::updateOrCreate(['name' => 'Giovanni Rossi'], [
            'role' => 'Presidente Nazionale', 'parent_id' => null, 'sort_order' => 0, 'is_active' => true,
        ]);
        $vice = OrgChartMember::updateOrCreate(['name' => 'Laura Bianchi'], [
            'role' => 'Vicepresidente', 'parent_id' => $presidente->id, 'sort_order' => 0, 'is_active' => true,
        ]);
        $segretario = OrgChartMember::updateOrCreate(['name' => 'Marco Verdi'], [
            'role' => 'Segretario Generale', 'parent_id' => $presidente->id, 'sort_order' => 1, 'is_active' => true,
        ]);
        OrgChartMember::updateOrCreate(['name' => 'Anna Neri'], [
            'role' => 'Consigliere', 'parent_id' => $vice->id, 'sort_order' => 0, 'is_active' => true,
        ]);
        OrgChartMember::updateOrCreate(['name' => 'Paolo Gialli'], [
            'role' => 'Consigliere', 'parent_id' => $segretario->id, 'sort_order' => 0, 'is_active' => true,
        ]);

        // Eventi
        Event::updateOrCreate(['slug' => 'convegno-nazionale'], [
            'title' => 'Convegno Nazionale Agenti',
            'description' => '<p>Una giornata di confronto e formazione.</p>',
            'location' => 'Milano, Centro Congressi',
            'starts_at' => now()->addDays(15)->setTime(9, 30),
            'ends_at' => now()->addDays(15)->setTime(18, 0),
            'registration_url' => 'https://sna.it/eventi/convegno/registrazione',
            'is_published' => true,
        ]);
        Event::updateOrCreate(['slug' => 'webinar-digitale'], [
            'title' => 'Webinar: digitalizzazione dello studio',
            'description' => '<p>Strumenti digitali per l\'agente moderno.</p>',
            'location' => 'Online',
            'starts_at' => now()->addDays(30)->setTime(17, 0),
            'is_published' => true,
        ]);

        // Notifica di esempio (bozza, visibile nel pannello)
        PushNotification::firstOrCreate(['title' => 'Benvenuto in SNAPP'], [
            'body' => 'Scopri le novità del sindacato direttamente in app.',
            'deep_link' => 'snapp://feed',
            'target' => 'all',
            'status' => 'draft',
        ]);
    }
}
