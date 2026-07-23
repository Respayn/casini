<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PhraseDuplicateHelper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhraseDuplicateHelperTest extends TestCase
{
    #[Test]
    public function test_normalizes_phrase_with_trim_and_lowercase(): void
    {
        $this->assertSame('болт гост', PhraseDuplicateHelper::normalize('  Болт ГОСТ  '));
    }

    #[Test]
    public function test_detects_duplicate_keys_across_regions(): void
    {
        $regions = [
            ['phrases' => ['болт', 'гайка']],
            ['phrases' => ['Гайка']],
        ];

        $this->assertSame(['гайка'], PhraseDuplicateHelper::duplicateKeys($regions));
    }

    #[Test]
    public function test_is_valid_for_save_requires_region_code_and_non_empty_phrases(): void
    {
        $valid = [
            ['code' => 213, 'phrases' => ['болт высокопрочный цена']],
        ];

        $invalid = [
            ['code' => null, 'phrases' => ['болт']],
        ];

        $this->assertTrue(PhraseDuplicateHelper::isValidForSave($valid));
        $this->assertFalse(PhraseDuplicateHelper::isValidForSave($invalid));
        $this->assertFalse(PhraseDuplicateHelper::isValidForSave([]));
    }
}
