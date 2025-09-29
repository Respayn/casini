<?php

namespace App\Data\Notifications;

use App\Models\Notification as NotificationModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Wireable;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class NotificationData extends Data implements Wireable
{
    use WireableData;

    /**
     * @param NotificationLinkData[]|DataCollection $links
     */
    public function __construct(
        public int $id,
        public int $userId,
        public string $text,
        public ?string $type,
        #[ArrayType] public DataCollection $links,
        public array $payload,
        public ?int $projectId,
        public ?string $projectName,
        public ?string $linkUrl,
        public Carbon $createdAt,
        public bool $isRead,
    ) {}

    /** Фабрика из модели */
    public static function fromModel(NotificationModel $n): static
    {
        return new static(
            id: $n->id,
            userId: $n->user_id,
            text: (string)($n->text ?? ''),
            type: $n->type,
            links: NotificationLinkData::collection($n->links ?? []),
            payload: (array)($n->payload ?? []),
            projectId: $n->project_id,
            projectName: data_get($n->payload, 'project'),
            linkUrl: $n->link_url,
            createdAt: $n->created_at ?? now(),
            isRead: !is_null($n->read_at),
        );
    }

    /** Коллекция DTO из коллекции моделей */
    public static function fromModels(Collection $models): DataCollection
    {
        return new DataCollection(static::class, $models->map(fn($m) => static::fromModel($m))->all());
    }

    /** Безопасный HTML с подстановкой ссылок [[key]] + автолинки + fallback linkUrl */
    public function html(): string
    {
        $text  = e($this->text);
        $links = collect($this->links->items());

        $text = preg_replace_callback('/\[\[([a-zA-Z0-9_-]+)\]\]/', function ($m) use ($links) {
            /** @var NotificationLinkData|null $item */
            $item = $links->first(fn($l) => $l instanceof NotificationLinkData && $l->key === $m[1]);
            if (!$item) return $m[0];
            $href = $item->safeHref();
            if (!$href) return e($item->label ?? $m[1]);
            $label = e($item->label ?? $href);
            $href  = e($href);
            return "<a href=\"{$href}\" target=\"_blank\" rel=\"noopener noreferrer\">{$label}</a>";
        }, $text);

        // автолинк http/https (относительные пути лучше отдавать через links)
        $text = preg_replace('~(?<!href=")(https?://[^\s<]+)~i', '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $text);

        // fallback "перейти"
        if ($this->linkUrl && preg_match('#^(/|https?:|mailto:)#i', $this->linkUrl)) {
            $href = e($this->linkUrl);
            $text .= ' <a href="'.$href.'" target="_blank" rel="noopener noreferrer">Перейти</a>';
        }

        return nl2br($text);
    }

    /** Цвета по макету */
    public function titleColor(): string { return $this->isRead ? '#6E8198' : '#283544'; }
    public function dateColor(): string  { return $this->isRead ? '#97A3B6' : '#486388'; }

    /** По умолчанию URL проекта не известен */
    public function projectHref(): ?string { return null; }
}
