<?php

namespace Tests\Unit\Enums;

use App\Enums\YandexMetrikaSearchEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YandexMetrikaSearchEngineTest extends TestCase
{
    /**
     * @return array<string, array{?string, YandexMetrikaSearchEngine}>
     */
    public static function apiNames(): array
    {
        return [
            'Yandex' => ['Yandex', YandexMetrikaSearchEngine::YANDEX],
            'Яндекс' => ['Яндекс', YandexMetrikaSearchEngine::YANDEX],
            'Google' => ['Google', YandexMetrikaSearchEngine::GOOGLE],
            'Google.org' => ['Google.org', YandexMetrikaSearchEngine::GOOGLE],
            'Mail.ru' => ['Mail.ru', YandexMetrikaSearchEngine::OTHER],
            'пустая строка' => ['', YandexMetrikaSearchEngine::OTHER],
            'null' => [null, YandexMetrikaSearchEngine::OTHER],
        ];
    }

    #[DataProvider('apiNames')]
    public function test_from_api_name(?string $name, YandexMetrikaSearchEngine $expected): void
    {
        $this->assertSame($expected, YandexMetrikaSearchEngine::fromApiName($name));
    }
}
