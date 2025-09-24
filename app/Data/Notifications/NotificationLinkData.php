<?php

namespace App\Data\Notifications;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class NotificationLinkData extends Data implements Wireable
{
    use WireableData;

    public function __construct(
        public ?string $key    = null,
        public ?string $label  = null,
        public ?string $route  = null,
        public array  $params  = [],
        public ?string $path   = null,
        public ?string $url    = null,
    ) {}

    public function href(): ?string
    {
        if ($this->route) {
            try { return route($this->route, $this->params, false); } catch (\Throwable) {}
        }
        if ($this->path && str_starts_with($this->path, '/')) return $this->path;
        if ($this->url) return $this->url;
        return null;
    }

    public function safeHref(): ?string
    {
        $h = $this->href();
        return ($h && preg_match('#^(/|https?:|mailto:)#i', $h)) ? $h : null;
    }
}
