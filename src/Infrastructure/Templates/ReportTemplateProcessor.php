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

    public function isMacroInTableRow(string $macro): bool
    {
        $completed = static::ensureMacroCompleted($macro);
        $pos = $this->findMacro($completed);
        if ($pos < 0) {
            return false;
        }

        $rowStart = $this->findXmlBlockStart($pos, 'w:tr');
        if ($rowStart < 0) {
            return false;
        }

        $rowEnd = $this->findXmlBlockEnd($rowStart, 'w:tr');
        if ($rowEnd < 0) {
            return false;
        }

        if (str_contains($this->getSlice($rowStart, $pos), '</w:tbl>')) {
            return false;
        }

        return str_contains($this->getSlice($rowStart, $rowEnd), $completed);
    }

    /**
     * Раскладывает ячейку с тегом в несколько колонок и подписывает шапку над ней.
     *
     * @param  list<string>  $columnLabels
     * @param  list<string>  $columnValues
     */
    public function expandTableCellColumns(string $macro, array $columnLabels, array $columnValues): bool
    {
        if ($columnLabels === [] || count($columnLabels) !== count($columnValues)) {
            return false;
        }

        $completed = static::ensureMacroCompleted($macro);
        $cell = $this->findContainingXmlBlockForMacro($completed, 'w:tc');
        if (! is_array($cell)) {
            return false;
        }

        $rowStart = $this->findXmlBlockStart($cell['start'], 'w:tr');
        if ($rowStart < 0) {
            return false;
        }

        $colIndex = $this->tableCellIndex($rowStart, $cell['start']);
        $cellXml = $this->getSlice($cell['start'], $cell['end']);
        $cloned = [];
        foreach ($columnValues as $value) {
            $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $cloned[] = str_replace($completed, $escaped, $cellXml);
        }

        $this->tempDocumentMainPart = $this->getSlice(0, $cell['start']).implode('', $cloned).$this->getSlice($cell['end']);

        $this->expandHeaderCell($rowStart, $colIndex, $columnLabels);

        return true;
    }

    /**
     * @param  list<string>  $columnLabels
     */
    private function expandHeaderCell(int $dataRowStart, int $colIndex, array $columnLabels): void
    {
        $tableStart = $this->findXmlBlockStart($dataRowStart, 'w:tbl');
        $prevRowEnd = strrpos($this->getSlice(0, $dataRowStart), '</w:tr>');
        if ($prevRowEnd === false) {
            return;
        }

        $prevRowStart = $this->findXmlBlockStart($prevRowEnd, 'w:tr');
        if ($prevRowStart < 0 || $prevRowStart >= $dataRowStart) {
            return;
        }

        if ($tableStart >= 0 && $prevRowStart < $tableStart) {
            return;
        }

        $headerRowEnd = $this->findXmlBlockEnd($prevRowStart, 'w:tr');
        if ($headerRowEnd < 0) {
            return;
        }

        $headerRowXml = $this->getSlice($prevRowStart, $headerRowEnd);
        $cells = $this->tableCells($headerRowXml);
        if (! isset($cells[$colIndex])) {
            return;
        }

        if (count($cells) >= $colIndex + count($columnLabels)) {
            return;
        }

        $headerCellXml = $cells[$colIndex];
        $expanded = [];
        foreach ($columnLabels as $label) {
            $expanded[] = $this->replaceCellText($headerCellXml, $label);
        }

        $before = implode('', array_slice($cells, 0, $colIndex));
        $after = implode('', array_slice($cells, $colIndex + 1));
        $open = preg_match('/^<w:tr\b[^>]*>/', $headerRowXml, $openMatch) === 1 ? $openMatch[0] : '<w:tr>';
        $newRow = $open.$before.implode('', $expanded).$after.'</w:tr>';

        $this->tempDocumentMainPart = $this->getSlice(0, $prevRowStart).$newRow.$this->getSlice($headerRowEnd);
    }

    private function tableCellIndex(int $rowStart, int $cellStart): int
    {
        $prefix = $this->getSlice($rowStart, $cellStart);

        return max(0, preg_match_all('/<w:tc[\s>]/', $prefix) ?: 0);
    }

    /**
     * @return list<string>
     */
    private function tableCells(string $rowXml): array
    {
        preg_match_all('/<w:tc\b[^>]*>.*?<\/w:tc>/s', $rowXml, $matches);

        return $matches[0] ?? [];
    }

    private function replaceCellText(string $cellXml, string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $replaced = false;
        $xml = preg_replace_callback(
            '/<w:t\b[^>]*>[^<]*<\/w:t>/',
            static function (array $match) use ($escaped, &$replaced): string {
                if ($replaced) {
                    return preg_replace('/(<w:t\b[^>]*>)[^<]*<\/w:t>/', '$1</w:t>', $match[0]) ?? $match[0];
                }

                $replaced = true;

                return preg_replace('/(<w:t\b[^>]*>)[^<]*<\/w:t>/', '$1'.$escaped.'</w:t>', $match[0]) ?? $match[0];
            },
            $cellXml,
        ) ?? $cellXml;

        if ($replaced) {
            return $xml;
        }

        if (preg_match('/<w:p\b[^>]*>/', $xml) === 1) {
            return preg_replace(
                '/(<w:p\b[^>]*>)(.*?)(<\/w:p>)/s',
                '$1<w:r><w:t xml:space="preserve">'.$escaped.'</w:t></w:r>$3',
                $xml,
                1,
            ) ?? $xml;
        }

        return preg_replace('/<\/w:tc>/', '<w:p><w:r><w:t xml:space="preserve">'.$escaped.'</w:t></w:r></w:p></w:tc>', $xml, 1) ?? $xml;
    }

    /**
     * @param  list<string>  $values
     */
    public function cloneParagraph(string $macro, array $values): void
    {
        if ($values === []) {
            return;
        }

        $completed = static::ensureMacroCompleted($macro);
        $block = $this->findContainingXmlBlockForMacro($completed, 'w:p');
        if (! is_array($block)) {
            throw new \RuntimeException('Can not clone paragraph, template variable not found.');
        }

        $xml = $this->getSlice($block['start'], $block['end']);
        $parts = [];
        foreach ($values as $value) {
            $raw = (string) $value;
            $depth = 0;
            while (str_starts_with($raw, "\t")) {
                $depth++;
                $raw = substr($raw, 1);
            }

            $escaped = htmlspecialchars($raw, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $escaped = str_replace(["\r\n", "\r", "\n"], '</w:t><w:br/><w:t>', $escaped);
            $parts[] = $this->paragraphWithIndent(str_replace($completed, $escaped, $xml), $depth);
        }

        $this->tempDocumentMainPart = $this->getSlice(0, $block['start']).implode('', $parts).$this->getSlice($block['end']);
    }

    private function paragraphWithIndent(string $xml, int $depth): string
    {
        if ($depth < 1) {
            return $xml;
        }

        $delta = $depth * 360;

        $xml = preg_replace_callback(
            '/<w:ilvl w:val="(\d+)"\s*\/>/',
            static fn (array $match): string => '<w:ilvl w:val="'.((int) $match[1] + $depth).'"/>',
            $xml,
            1,
        ) ?? $xml;

        $replacedIndent = false;
        $xml = preg_replace_callback(
            '/<w:ind\b([^>]*)\/>/',
            static function (array $match) use ($delta, &$replacedIndent): string {
                $replacedIndent = true;
                $attrs = $match[1];
                if (preg_match('/w:left="(\d+)"/', $attrs, $left) === 1) {
                    $attrs = preg_replace('/w:left="\d+"/', 'w:left="'.((int) $left[1] + $delta).'"', $attrs, 1);
                } else {
                    $attrs .= ' w:left="'.$delta.'"';
                }

                return '<w:ind'.$attrs.'/>';
            },
            $xml,
            1,
        ) ?? $xml;

        if ($replacedIndent) {
            return $xml;
        }

        $indent = '<w:ind w:left="'.$delta.'"/>';
        if (preg_match('/<w:pPr\b[^>]*>/', $xml, $match, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $match[0][1] + strlen($match[0][0]);

            return substr($xml, 0, $offset).$indent.substr($xml, $offset);
        }

        return preg_replace('/<w:p\b[^>]*>/', '$0<w:pPr>'.$indent.'</w:pPr>', $xml, 1) ?? $xml;
    }
}
