<?php

namespace Tests\Unit\Views;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ReviewMarkupGuardTest extends TestCase
{
    private const VIEWS = __DIR__.'/../../../resources/views/';

    /**
     * @return array<string, array{string}>
     */
    public static function styledViews(): array
    {
        return [
            'модалка Метрики' => ['components/project-form/yandex-metrika-integration-modal-body.blade.php'],
            'сайдбар Метрики' => ['components/project-form/yandex-metrika-integration-modal-sidebar.blade.php'],
            'модалка Google Таблиц' => ['components/project-form/google-sheets-integration-modal-body.blade.php'],
            'иконка Google Таблиц' => ['components/icons/logo/google-sheets.blade.php'],
            'иконка «Обновить»' => ['components/icons/refresh.blade.php'],
            'date-picker' => ['components/form/date-picker.blade.php'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function textViews(): array
    {
        return [
            'окно Метрики' => ['oauth/yandex-metrika-popup-complete.blade.php'],
            'окно Метрики без настроек' => ['oauth/yandex-metrika-oauth-unconfigured.blade.php'],
            'окно Google Таблиц' => ['oauth/google-sheets-popup-complete.blade.php'],
            'окно Google Таблиц без настроек' => ['oauth/google-sheets-oauth-unconfigured.blade.php'],
            'сайдбар Метрики' => ['components/project-form/yandex-metrika-integration-modal-sidebar.blade.php'],
        ];
    }

    #[DataProvider('styledViews')]
    public function test_view_has_no_static_inline_style(string $view): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/(?<![\w:-])style="/u',
            $this->read($view),
            'Оформление задаётся классами Tailwind, а не атрибутом style.',
        );
    }

    #[DataProvider('textViews')]
    public function test_user_texts_have_no_long_dashes(string $view): void
    {
        $lines = preg_split('/\R/u', $this->read($view));
        $userLines = array_filter($lines, fn (string $line) => ! preg_match('#^\s*(//|\{\{--|<!--|\*)#u', $line));

        foreach ($userLines as $line) {
            $this->assertDoesNotMatchRegularExpression('/[—–]/u', $line, 'Длинное тире в тексте интерфейса: '.trim($line));
        }
    }

    public function test_replaced_styles_are_tailwind_classes(): void
    {
        $metrika = $this->read('components/project-form/yandex-metrika-integration-modal-body.blade.php');

        $this->assertStringContainsString('z-[1000]', $metrika);
        $this->assertStringContainsString('max-h-[196px] overflow-y-auto', $metrika);
        $this->assertStringContainsString('class="max-h-[500px]"', $metrika);
        $this->assertStringContainsString(
            'mask-[url(/images/icons/refresh.png)]',
            $this->read('components/icons/refresh.blade.php'),
        );
    }

    private function read(string $view): string
    {
        $path = self::VIEWS.$view;
        $this->assertFileExists($path);

        return (string) file_get_contents($path);
    }
}
