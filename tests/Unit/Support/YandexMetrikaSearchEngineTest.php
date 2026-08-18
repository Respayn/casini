<?php

namespace Tests\Unit\Support;

use App\Support\YandexMetrikaSearchEngine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaSearchEngineTest extends TestCase
{
    #[Test]
    public function test_normalizes_yandex_and_google(): void
    {
        $this->assertSame('yandex', YandexMetrikaSearchEngine::normalize('Yandex'));
        $this->assertSame('yandex', YandexMetrikaSearchEngine::normalize('Яндекс'));
        $this->assertSame('google', YandexMetrikaSearchEngine::normalize('Google'));
        $this->assertSame('google', YandexMetrikaSearchEngine::normalize('Google.org'));
        $this->assertSame('other', YandexMetrikaSearchEngine::normalize('Mail.ru'));
        $this->assertSame('other', YandexMetrikaSearchEngine::normalize(''));
        $this->assertSame('other', YandexMetrikaSearchEngine::normalize(null));
    }
}
