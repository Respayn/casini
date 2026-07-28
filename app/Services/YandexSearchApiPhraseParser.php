<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;

class YandexSearchApiPhraseParser
{
    /**
     * @return string[]
     */
    public function parseFromPath(string $path): array
    {
        try {
            $phpWord = IOFactory::load($path);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Не удалось прочитать файл .docx: '.$exception->getMessage(), 0, $exception);
        }

        $lines = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $lines = array_merge($lines, $this->extractLines($element));
            }
        }

        return array_values(array_filter(array_map(
            static fn (string $line) => trim($line),
            $lines
        ), static fn (string $line) => $line !== ''));
    }

    /**
     * @return string[]
     */
    private function extractLines(mixed $element): array
    {
        if ($element instanceof TextRun) {
            return $this->splitLines((string) $element->getText());
        }

        if ($element instanceof Text) {
            $text = trim((string) $element->getText());

            return $text !== '' ? [$text] : [];
        }

        if (method_exists($element, 'getElements')) {
            $lines = [];

            foreach ($element->getElements() as $child) {
                $lines = array_merge($lines, $this->extractLines($child));
            }

            return $lines;
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function splitLines(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return explode("\n", $text);
    }
}
