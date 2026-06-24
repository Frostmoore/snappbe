# SNAPP — Backend

API REST e pannello di amministrazione dell'app **SNAPP**. Espone i dati al client mobile, gestisce autenticazione e notifiche e si integra con un sito di contenuti esterno.

## Stack

- **Laravel 12** (PHP **8.2+**)
- **Laravel Sanctum** — autenticazione a token
- **Laravel Socialite** + **firebase/php-jwt** — login social (Google/Apple)
- **Filament v3** — pannello di amministrazione
- **Redis** (code e cache) via `predis/predis`
- **MariaDB / MySQL**

## Architettura

Il backend fa da ponte tra tre sistemi: l'app mobile, il pannello di amministrazione e un sito di contenuti esterno.

I contenuti già pubblicati sul sito esterno (articoli, newsletter, eventi) **non vengono duplicati** nel database: sono esposti all'app in tempo reale attraverso un livello di proxy con cache a breve durata, mantenuta calda in background per evitare latenze quando la sorgente risponde "a freddo". La comunicazione server-to-server con la sorgente avviene su un canale autenticato.

Il resto dei contenuti è nativo e gestito dal pannello. L'invio delle notifiche push è incapsulato dietro un'astrazione indipendente dal provider, con selezione del pubblico (tutti, per livello d'iscrizione, per ruolo o per singoli utenti) ed elaborazione in coda.

## Requisiti

- PHP 8.2+ con estensione `intl`
- Composer
- MariaDB/MySQL
- Redis

## Installazione

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Configurazione

Le impostazioni si definiscono in `.env`. I gruppi principali:

- **Database** (`DB_*`)
- **Mailer SMTP** (`MAIL_*`)
- **Cache e code** (`CACHE_STORE`, `QUEUE_CONNECTION`, `REDIS_*`)
- **Credenziali OAuth** (Google / Apple)
- **Provider notifiche push**
- **Parametri di integrazione con la sorgente di contenuti esterna**

I valori non sono versionati: vanno impostati per ogni ambiente.

## Avvio (sviluppo)

```bash
php artisan serve            # API
php artisan queue:work       # elaborazione code (notifiche, email)
php artisan schedule:work    # task schedulati
```

Pannello di amministrazione: `/admin`.

## Test

```bash
php artisan test
```

La suite di feature test copre i flussi principali: autenticazione e login social, esposizione dei contenuti della sorgente esterna (con HTTP simulato), visibilità dei contenuti per profilo e invio delle notifiche.

## Struttura

```
app/
  Http/           # controller delle API e middleware
  Models/         # modelli Eloquent
  Services/       # logica applicativa e integrazioni esterne
  Filament/       # risorse del pannello di amministrazione
  Notifications/
database/         # migrazioni e seeder
routes/           # definizione delle rotte
tests/            # feature e unit test
```

## Funzionalità

- Registrazione, verifica email, login e reset password.
- Login social (Google/Apple) con verifica del token lato server.
- Notifiche push verso il client, con selezione del pubblico e deep-link.
- Esposizione in tempo reale dei contenuti della sorgente esterna (articoli, newsletter, eventi).
- Gestione dei contenuti dell'app dal pannello: contenuti per profilo, eventi, organigramma, area riservata e sezioni informative.

## Note

- Client mobile: repository **snappfe**.
- Credenziali, chiavi e segreti non sono inclusi nel repository e vanno configurati per ogni ambiente.
