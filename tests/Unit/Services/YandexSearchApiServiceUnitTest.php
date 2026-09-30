<?php

namespace Tests\Unit\Services;

use App\Services\YandexSearchApiService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexSearchApiServiceUnitTest extends TestCase
{
    #[Test]
    public function test_resolve_position_for_domain_matches_host_and_subdomain(): void
    {
        $service = new YandexSearchApiService();
        $results = [
            ['position' => 1, 'url' => 'https://other.ru/page', 'title' => 'Other'],
            ['position' => 2, 'url' => 'https://www.example.com/seo', 'title' => 'Example'],
        ];

        $this->assertSame(2, $service->resolvePositionForDomain($results, 'example.com'));
        $this->assertSame(2, $service->resolvePositionForDomain($results, 'https://example.com'));
        $this->assertNull($service->resolvePositionForDomain($results, 'missing.ru'));
    }

    #[Test]
    public function test_parse_organic_results_from_base64_xml(): void
    {
        $service = new YandexSearchApiService();
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
        $service = new YandexSearchApiService();
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
