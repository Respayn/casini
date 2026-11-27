<?php

namespace Tests\Unit\Services;

use App\Services\YandexSearchApiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexSearchApiServiceUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.yandex_search_api.api_key' => 'test-key',
            'services.yandex_search_api.folder_id' => 'test-folder',
            'services.yandex_search_api.base_url' => 'https://searchapi.test/v2/web/search',
            'services.yandex_search_api.min_interval_ms' => 0,
        ]);

        Cache::forget('yandex_search_api.rate_limit.next_at_ms');
    }

    #[Test]
    public function test_resolve_position_for_domain_matches_host_and_subdomain(): void
    {
        $service = new YandexSearchApiService;
        $results = [
            ['position' => 1, 'url' => 'https://other.ru/page', 'title' => 'Other'],
            ['position' => 2, 'url' => 'https://www.example.com/seo', 'title' => 'Example'],
        ];

        $this->assertSame(2, $service->resolvePositionForDomain($results, 'example.com'));
        $this->assertSame(2, $service->resolvePositionForDomain($results, 'https://example.com'));
        $this->assertNull($service->resolvePositionForDomain($results, 'missing.ru'));
    }

    #[Test]
    public function test_search_web_waits_min_interval_between_requests(): void
    {
        config(['services.yandex_search_api.min_interval_ms' => 200]);
        Cache::forget('yandex_search_api.rate_limit.next_at_ms');

        Http::fake([
            'searchapi.test/*' => Http::response([
                'groups' => [
                    ['documents' => [['url' => 'https://example.com', 'title' => 'Example']]],
                ],
            ], 200),
        ]);

        $service = new YandexSearchApiService;

        $startedAt = microtime(true);
        $service->searchWeb('фраза один', 213);
        $service->searchWeb('фраза два', 213);
        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        $this->assertGreaterThanOrEqual(180, $elapsedMs);
        Http::assertSentCount(2);
    }

    #[Test]
    public function test_search_web_retries_same_phrase_on_429(): void
    {
        config(['services.yandex_search_api.min_interval_ms' => 0]);

        Http::fake([
            'searchapi.test/*' => Http::sequence()
                ->push(['message' => 'rate quota limit exceed'], 429)
                ->push([
                    'groups' => [
                        ['documents' => [['url' => 'https://example.com', 'title' => 'Example']]],
                    ],
                ], 200),
        ]);

        $service = new YandexSearchApiService;
        $results = $service->searchWeb('фраза с повтором', 213);

        $this->assertCount(1, $results);
        $this->assertSame('https://example.com', $results[0]['url']);
        Http::assertSentCount(2);
    }

    #[Test]
    public function test_search_web_throws_after_exhausted_429_retries(): void
    {
        config(['services.yandex_search_api.min_interval_ms' => 0]);

        Http::fake([
            'searchapi.test/*' => Http::response(['message' => 'rate quota limit exceed'], 429),
        ]);

        $service = new YandexSearchApiService;

        try {
            $service->searchWeb('фраза без удачи', 213);
            $this->fail('Ожидался RequestException после исчерпания повторов 429');
        } catch (RequestException) {
            Http::assertSentCount(3);
        }
    }

    #[Test]
    public function test_parse_organic_results_from_base64_xml(): void
    {
        $service = new YandexSearchApiService;
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<yandexsearch version="1.0">
  <response>
    <results>
      <grouping>
        <group>
          <doc>
            <url>https://a.ru/page</url>
            <title>A</title>
          </doc>
        </group>
        <group>
          <doc>
            <url>https://b.ru/page</url>
            <title>B</title>
          </doc>
        </group>
      </grouping>
    </results>
  </response>
</yandexsearch>
XML;

        $parsed = $service->parseOrganicResults([
            'rawData' => base64_encode($xml),
        ]);

        $this->assertCount(2, $parsed);
        $this->assertSame(1, $parsed[0]['position']);
        $this->assertSame('https://a.ru/page', $parsed[0]['url']);
        $this->assertSame(2, $parsed[1]['position']);
    }

    #[Test]
    public function test_parse_organic_results_from_groups(): void
    {
        $service = new YandexSearchApiService;
        $parsed = $service->parseOrganicResults([
            'groups' => [
                ['documents' => [['url' => 'https://a.ru', 'title' => 'A']]],
                ['documents' => [['url' => 'https://b.ru', 'title' => 'B']]],
            ],
        ]);

        $this->assertCount(2, $parsed);
        $this->assertSame(1, $parsed[0]['position']);
        $this->assertSame('https://a.ru', $parsed[0]['url']);
        $this->assertSame(2, $parsed[1]['position']);
    }
}
