<?php

namespace App\Notifications\Push;

/**
 * Esito aggregato di un invio push.
 */
class PushResult
{
    /** @param array<int,string> $invalidTokens token rifiutati (da rimuovere) */
    public function __construct(
        public int $success = 0,
        public int $failure = 0,
        public array $invalidTokens = [],
    ) {}

    public function merge(PushResult $other): self
    {
        return new self(
            $this->success + $other->success,
            $this->failure + $other->failure,
            array_merge($this->invalidTokens, $other->invalidTokens),
        );
    }

    /** @return array<string,int> */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'failure' => $this->failure,
            'invalid' => count($this->invalidTokens),
        ];
    }
}
