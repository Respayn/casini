<?php

namespace Tests\Unit\Domain\YandexMetrika;

use PHPUnit\Framework\TestCase;
use Src\Domain\YandexMetrika\SearchPhraseNormalizer;

class SearchPhraseNormalizerTest extends TestCase
{
    public function test_collapses_case_spaces_and_nbsp(): void
    {
        $this->assertSame(
            'ммк метиз',
            SearchPhraseNormalizer::normalize("  ММК\u{00A0}метиз  "),
        );
        $this->assertSame(
            SearchPhraseNormalizer::normalize('ммк метиз'),
            SearchPhraseNormalizer::normalize('ММК  метиз'),
        );
    }

    public function test_truncates_to_255_characters(): void
    {
        $phrase = str_repeat('ф', 300);

        $normalized = SearchPhraseNormalizer::normalize($phrase);

        $this->assertSame(255, mb_strlen($normalized, 'UTF-8'));
        $this->assertSame(str_repeat('ф', 255), $normalized);
    }

    public function test_empty_phrase_stays_empty(): void
    {
        $this->assertSame('', SearchPhraseNormalizer::normalize(" \n\t "));
    }
}
