<?php

namespace Tests\Unit\Services\Bitrix24;

use App\Services\Bitrix24\Bitrix24ApiException;
use App\Services\Bitrix24\Bitrix24Client;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Bitrix24ClientTest extends TestCase
{
    private const WEBHOOK = 'https://company.bitrix24.ru/rest/1/secret-key/';

    public function test_descendant_tasks_walks_nested_children(): void
    {
        Http::fake(function (Request $request) {
            $parentId = $request->data()['filter']['PARENT_ID'] ?? null;

            $tasks = match ($parentId) {
                100 => [['id' => '101', 'title' => 'SEO: аудит'], ['id' => '102', 'title' => 'Прочее']],
                101 => [['id' => '103', 'title' => 'SEO: тексты']],
                default => [],
            };

            return Http::response(['result' => ['tasks' => $tasks], 'total' => count($tasks)]);
        });

        $tasks = (new Bitrix24Client(pauseMs: 0))->descendantTasks(self::WEBHOOK, 100);

        $this->assertSame([101, 102, 103], array_column($tasks, 'id'));
        $this->assertSame('SEO: тексты', $tasks[2]['title']);
        Http::assertSent(fn (Request $request) => $request->url() === self::WEBHOOK.'tasks.task.list.json');
    }

    public function test_base_url_drops_sample_method_after_key(): void
    {
        $this->assertSame('https://company.bitrix24.ru/rest/1/secret-key', Bitrix24Client::baseUrl(self::WEBHOOK));
        $this->assertSame('https://company.bitrix24.ru/rest/1/secret-key', Bitrix24Client::baseUrl('https://company.bitrix24.ru/rest/1/secret-key/profile.json'));
        $this->assertSame('https://company.bitrix24.ru/rest/1/secret-key', Bitrix24Client::baseUrl(' https://company.bitrix24.ru/rest/1/secret-key '));
    }

    public function test_elapsed_items_skip_empty_records(): void
    {
        Http::fake([
            '*task.elapseditem.getlist.json' => Http::response(['result' => [
                ['ID' => '1', 'USER_ID' => '42', 'SECONDS' => '3600', 'CREATED_DATE' => '2026-09-29T10:00:00+03:00'],
                ['ID' => '2', 'USER_ID' => '42', 'SECONDS' => '0', 'CREATED_DATE' => '2026-09-29T11:00:00+03:00'],
                ['ID' => '3', 'USER_ID' => '0', 'SECONDS' => '600', 'CREATED_DATE' => '2026-09-29T12:00:00+03:00'],
            ]]),
        ]);

        $items = (new Bitrix24Client(pauseMs: 0))->elapsedItems(
            self::WEBHOOK,
            103,
            Carbon::parse('2026-09-29 00:00:00', 'Europe/Moscow'),
            Carbon::parse('2026-09-29 23:59:59', 'Europe/Moscow'),
        );

        $this->assertCount(1, $items);
        $this->assertSame(42, $items[0]['user_id']);
        $this->assertSame(3600, $items[0]['seconds']);
    }

    public function test_api_error_message_does_not_contain_webhook(): void
    {
        Http::fake(['*' => Http::response(['error' => 'NO_AUTH_FOUND', 'error_description' => 'Wrong key'], 401)]);

        try {
            (new Bitrix24Client(pauseMs: 0))->descendantTasks(self::WEBHOOK, 100);
            $this->fail('Ожидалась ошибка Битрикс24');
        } catch (Bitrix24ApiException $e) {
            $this->assertStringContainsString('NO_AUTH_FOUND', $e->getMessage());
            $this->assertStringNotContainsString('secret-key', $e->getMessage());
        }
    }

    public function test_query_limit_is_retried_limited_times(): void
    {
        Http::fake(['*' => Http::response(['error' => 'QUERY_LIMIT_EXCEEDED'], 503)]);

        try {
            (new Bitrix24Client(pauseMs: 0))->descendantTasks(self::WEBHOOK, 100);
            $this->fail('Ожидалась ошибка Битрикс24');
        } catch (Bitrix24ApiException) {
            Http::assertSentCount(Bitrix24Client::MAX_ATTEMPTS);
        }
    }
}
