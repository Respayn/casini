<?php

namespace App\Data\IntegrationSync;

readonly class IntegrationSyncResult
{
    public function __construct(
        public bool $ok,
        public ?string $error = null,
        public bool $requeue = false,
    ) {}

    public static function success(): self
    {
        return new self(ok: true);
    }

    public static function failure(string $error, bool $requeue = true): self
    {
        return new self(ok: false, error: $error, requeue: $requeue);
    }
}
