<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id','text','link_url','links','type','payload','project_id','read_at',
        'notifiable_type','notifiable_id',
    ];

    protected $casts = [
        'links'   => 'array',
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    // Готовый безопасный HTML для вывода
    protected $appends = ['html'];

    public function getHtmlAttribute(): string
    {
        $text  = e($this->text ?? '');
        $links = collect($this->links ?? []);

        // [[key]] → <a>
        $text = preg_replace_callback('/\[\[([a-zA-Z0-9_-]+)\]\]/', function ($m) use ($links) {
            $item = $links->firstWhere('key', $m[1]);
            if (!$item) return $m[0];

            $href = $this->resolveHref($item);
            if (!$href || !$this->isSafeHref($href)) {
                return e($item['label'] ?? $m[1]);
            }
            $label = e($item['label'] ?? $href);
            $href  = e($href);
            return "<a href=\"{$href}\" target=\"_blank\" rel=\"noopener noreferrer\">{$label}</a>";
        }, $text);

        // автолинк только http/https
        $text = preg_replace(
            '~(?<!href=")(https?://[^\s<]+)~i',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $text
        );

        // fallback на одиночную ссылку
        if ($this->link_url && $this->isSafeHref($this->link_url)) {
            $href = e($this->link_url);
            $text .= ' <a href="'.$href.'" target="_blank" rel="noopener noreferrer">Перейти</a>';
        }

        return nl2br($text);
    }

    /**
     * Поддерживаем 3 варианта элемента links[]:
     *  - ['route' => 'system-settings.clients-and-projects.projects.manage', 'params' => ['projectId'=>1]]
     *  - ['path'  => '/absolute/path']
     *  - ['url'   => 'https://ext.tld/…']
     */
    private function resolveHref(array $item): ?string
    {
        if (!empty($item['route'])) {
            try { return route($item['route'], $item['params'] ?? [], false); } catch (\Throwable) {}
        }
        if (!empty($item['path']) && str_starts_with($item['path'], '/')) {
            return $item['path'];
        }
        if (!empty($item['url'])) {
            return $item['url'];
        }
        return null;
    }

    /** Разрешаем относительный путь, http/https, mailto */
    private function isSafeHref(string $href): bool
    {
        return (bool) preg_match('#^(/|https?:|mailto:)#i', $href);
    }
}
