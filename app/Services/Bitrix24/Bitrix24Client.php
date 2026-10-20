<?php

namespace App\Services\Bitrix24;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * REST Битрикс24 через входящий вебхук агентства (https://портал/rest/{user}/{ключ}/).
 */
class Bitrix24Client
{
    public const MAX_ATTEMPTS = 3;

    public const MAX_PAGES = 40;

    public const MAX_TASKS = 1000;

    public const MAX_DEPTH = 10;

    private const PAGE_SIZE = 50;

    private const TIMEOUT_SECONDS = 20;

    public function __construct(
        private readonly int $pauseMs = 300,
    ) {}

    /**
     * Все вложенные подзадачи корневой задачи (сама корневая не входит).
     *
     * @return list<array{id: int, title: string, status: int, closed_at: ?Carbon}>
     */
    public function descendantTasks(string $webhook, int $rootTaskId): array
    {
        $result = [];
        $seen = [$rootTaskId => true];
        $level = [$rootTaskId];

        for ($depth = 0; $depth < self::MAX_DEPTH && $level !== []; $depth++) {
            $next = [];

            foreach ($level as $parentId) {
                foreach ($this->childTasks($webhook, $parentId) as $task) {
                    if (isset($seen[$task['id']])) {
                        continue;
                    }

                    $seen[$task['id']] = true;
                    $result[] = $task;
                    $next[] = $task['id'];

                    if (count($result) >= self::MAX_TASKS) {
                        return $result;
                    }
                }
            }

            $level = $next;
        }

        return $result;
    }

    /**
     * Записи «затраченное время» задачи, созданные в периоде.
     *
     * @return list<array{user_id: int, seconds: int, created_at: Carbon}>
     */
    public function elapsedItems(string $webhook, int $taskId, Carbon $from, Carbon $to): array
    {
        $items = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = $this->call($webhook, 'task.elapseditem.getlist', [
                $taskId,
                ['ID' => 'asc'],
                [
                    '>=CREATED_DATE' => $from->toAtomString(),
                    '<=CREATED_DATE' => $to->toAtomString(),
                ],
                ['ID', 'USER_ID', 'SECONDS', 'CREATED_DATE'],
                ['NAV_PARAMS' => ['nPageSize' => self::PAGE_SIZE, 'iNumPage' => $page]],
            ]);

            $rows = is_array($response['result'] ?? null) ? $response['result'] : [];

            foreach ($rows as $row) {
                $userId = (int) ($row['USER_ID'] ?? 0);
                $seconds = (int) ($row['SECONDS'] ?? 0);
                $createdAt = $row['CREATED_DATE'] ?? null;

                if ($userId <= 0 || $seconds <= 0 || ! filled($createdAt)) {
                    continue;
                }

                $items[] = [
                    'user_id' => $userId,
                    'seconds' => $seconds,
                    'created_at' => Carbon::parse((string) $createdAt),
                ];
            }

            if (count($rows) < self::PAGE_SIZE) {
                break;
            }
        }

        return $items;
    }

    /**
     * Комментарии задачи.
     *
     * @return list<array{text: string, posted_at: Carbon}>
     */
    public function taskComments(string $webhook, int $taskId): array
    {
        $comments = [];

        $response = $this->call($webhook, 'task.commentitem.getlist', [
            $taskId,
            ['ID' => 'asc'],
            [],
        ]);

        foreach ($this->commentRows($response['result'] ?? null) as $row) {
            $text = (string) ($row['POST_MESSAGE'] ?? $row['postMessage'] ?? $row['POST_MESSAGE_HTML'] ?? $row['postMessageHtml'] ?? '');
            $postedAt = $row['POST_DATE'] ?? $row['postDate'] ?? null;

            if ($text === '' || ! filled($postedAt)) {
                continue;
            }

            $comments[] = [
                'text' => $text,
                'posted_at' => Carbon::parse((string) $postedAt),
            ];
        }

        return $comments;
    }

    /**
     * Пункты чек-листа задачи.
     *
     * @return list<array{title: string, is_complete: bool, toggled_at: ?Carbon}>
     */
    public function taskChecklist(string $webhook, int $taskId): array
    {
        $items = [];

        $response = $this->call($webhook, 'task.checklistitem.getlist', [
            $taskId,
            ['ID' => 'asc'],
        ]);

        foreach ($this->commentRows($response['result'] ?? null) as $row) {
            $title = trim((string) ($row['TITLE'] ?? $row['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'is_complete' => Bitrix24WorkParser::isChecklistComplete($row['IS_COMPLETE'] ?? $row['isComplete'] ?? false),
                'toggled_at' => self::optionalDate($row['TOGGLED_DATE'] ?? $row['toggledDate'] ?? null),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: int, title: string, status: int, closed_at: ?Carbon}>
     */
    private function childTasks(string $webhook, int $parentId): array
    {
        $tasks = [];
        $start = 0;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $this->call($webhook, 'tasks.task.list', [
                'filter' => ['PARENT_ID' => $parentId],
                'select' => ['ID', 'TITLE', 'STATUS', 'CLOSED_DATE'],
                'order' => ['ID' => 'asc'],
                'start' => $start,
            ]);

            $rows = $response['result']['tasks'] ?? [];

            foreach (is_array($rows) ? $rows : [] as $row) {
                $id = (int) ($row['id'] ?? $row['ID'] ?? 0);

                if ($id > 0) {
                    $tasks[] = [
                        'id' => $id,
                        'title' => (string) ($row['title'] ?? $row['TITLE'] ?? ''),
                        'status' => self::intStatus($row),
                        'closed_at' => self::optionalDate($row['closedDate'] ?? $row['CLOSED_DATE'] ?? null),
                    ];
                }
            }

            if (! isset($response['next'])) {
                break;
            }

            $start = (int) $response['next'];
        }

        return $tasks;
    }

    /**
     * @param  array<int|string, mixed>  $params
     * @return array<string, mixed>
     */
    private function call(string $webhook, string $method, array $params): array
    {
        $url = self::baseUrl($webhook).'/'.$method.'.json';
        $lastError = 'Нет ответа от Битрикс24';

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $this->pause();

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->post($url, $params);
            } catch (ConnectionException) {
                $lastError = 'Нет связи с Битрикс24';

                continue;
            }

            $body = $response->json();
            $error = is_array($body) ? ($body['error'] ?? null) : null;

            if ($error === 'QUERY_LIMIT_EXCEEDED' || $response->serverError()) {
                $lastError = 'Битрикс24 временно ограничил запросы';

                continue;
            }

            if ($error !== null) {
                throw new Bitrix24ApiException('Битрикс24 вернул ошибку: '.$error);
            }

            if (! $response->successful() || ! is_array($body)) {
                throw new Bitrix24ApiException('Битрикс24 ответил с кодом '.$response->status());
            }

            return $body;
        }

        throw new Bitrix24ApiException($lastError);
    }

    /**
     * Адрес до ключа: в Битрикс24 вебхук часто копируют вместе с примером вызова (…/rest/1/ключ/profile.json).
     */
    public static function baseUrl(string $webhook): string
    {
        $webhook = trim($webhook);

        return rtrim((string) preg_replace('#(/rest/\d+/[^/?]+).*$#', '$1', $webhook), '/');
    }

    private function pause(): void
    {
        if ($this->pauseMs > 0) {
            usleep($this->pauseMs * 1000);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function intStatus(array $row): int
    {
        $raw = $row['status'] ?? $row['STATUS'] ?? 0;

        if (is_array($raw)) {
            $raw = $raw['id'] ?? $raw['value'] ?? 0;
        }

        return (int) $raw;
    }

    private static function optionalDate(mixed $value): ?Carbon
    {
        if (! filled($value) || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return Carbon::parse((string) $value);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function commentRows(mixed $result): array
    {
        if (! is_array($result) || $result === []) {
            return [];
        }

        if (array_is_list($result)) {
            return array_values(array_filter($result, 'is_array'));
        }

        $nested = $result['comments'] ?? $result['items'] ?? null;

        if (is_array($nested)) {
            return array_values(array_filter($nested, 'is_array'));
        }

        return array_values(array_filter($result, 'is_array'));
    }
}
