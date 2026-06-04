<?php

namespace App\Notifications\Push;

/**
 * Messaggio push universale, indipendente dal transport (FCM oggi, altri domani).
 *
 * Contiene TUTTI i parametri utili a una notifica: titolo, testo, immagine,
 * deep-link, dati arbitrari, suono, badge, priorità, canale Android, click action.
 * Costruibile con argomenti nominati o col builder fluente `PushMessage::make(...)->withImage(...)`.
 */
class PushMessage
{
    /**
     * @param array<string,string|int|null> $data dati arbitrari consegnati all'app
     */
    public function __construct(
        public string $title,
        public string $body,
        public ?string $image = null,
        public ?string $deepLink = null,
        public array $data = [],
        public ?string $sound = 'default',
        public ?int $badge = null,
        public ?string $clickAction = null,
        public string $priority = 'high',
        public ?string $androidChannelId = null,
    ) {}

    public static function make(string $title, string $body): self
    {
        return new self($title, $body);
    }

    public function withImage(?string $url): self
    {
        $this->image = $url;

        return $this;
    }

    public function withDeepLink(?string $deepLink): self
    {
        $this->deepLink = $deepLink;

        return $this;
    }

    /** @param array<string,string|int|null> $data */
    public function withData(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function withSound(?string $sound): self
    {
        $this->sound = $sound;

        return $this;
    }

    public function withBadge(?int $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function withClickAction(?string $action): self
    {
        $this->clickAction = $action;

        return $this;
    }

    public function withAndroidChannel(?string $channelId): self
    {
        $this->androidChannelId = $channelId;

        return $this;
    }

    /**
     * Dati consegnati all'app (deep-link + click action confluiscono qui, come stringhe).
     *
     * @return array<string,string>
     */
    public function dataPayload(): array
    {
        $payload = $this->data;

        if ($this->deepLink !== null) {
            $payload['deep_link'] = $this->deepLink;
        }
        if ($this->clickAction !== null) {
            $payload['click_action'] = $this->clickAction;
        }

        // FCM richiede valori stringa nel blocco data.
        return array_map(static fn ($v) => (string) $v, array_filter($payload, static fn ($v) => $v !== null));
    }
}
