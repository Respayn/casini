<?php

namespace Src\Application\Templates;

interface TemplateTagReaderInterface
{
    /**
     * Теги шаблона без обрамления {{...}}, без закрывающих тегов блоков (/имя).
     *
     * @return string[]|null null, если файл шаблона не удалось прочитать
     */
    public function read(string $path): ?array;
}
