<?php

namespace Src\Infrastructure\Templates;

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Шаблон отчета с тегами вида {{имя}}.
 * Скобки тегов в PhpWord хранятся в статическом свойстве и применяются уже в конструкторе
 * (склейка тегов, разорванных Word на части), поэтому задаются до parent::__construct().
 *
 * Штатный fixBrokenMacros PhpWord рассчитан на ${...} и не склеивает {{...}},
 * когда Word разносит скобки по отдельным runs: { | { | имя | } | }.
 */
class ReportTemplateProcessor extends TemplateProcessor
{
    public const OPENING_CHARS = '{{';

    public const CLOSING_CHARS = '}}';

    public function __construct(string $documentTemplate)
    {
        self::$macroOpeningChars = self::OPENING_CHARS;
        self::$macroClosingChars = self::CLOSING_CHARS;

        parent::__construct($documentTemplate);
    }

    /**
     * @param  string  $macro
     */
    protected static function ensureMacroCompleted($macro): string
    {
        $open = self::$macroOpeningChars;
        $close = self::$macroClosingChars;

        if (
            substr($macro, 0, strlen($open)) !== $open
            && substr($macro, -strlen($close)) !== $close
        ) {
            return $open.$macro.$close;
        }

        return $macro;
    }

    /**
     * @param  string  $documentPart
     */
    protected function fixBrokenMacros($documentPart): string
    {
        // Не пересекаем границы абзаца: иначе одиночные { и } в разных местах склеятся ошибочно.
        return preg_replace_callback(
            '/\{(?:(?!\{)(?!<\/w:p>).)*?\{(?:(?!\})(?!<\/w:p>).)*?\}(?:(?!\})(?!<\/w:p>).)*?\}/s',
            static fn (array $match): string => strip_tags($match[0]),
            $documentPart
        ) ?? $documentPart;
    }
}
