<?php

namespace Src\Infrastructure\Templates;

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Шаблон отчета с тегами вида {{имя}}.
 * Скобки тегов в PhpWord хранятся в статическом свойстве и применяются уже в конструкторе
 * (склейка тегов, разорванных Word на части), поэтому задаются до parent::__construct().
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
}
