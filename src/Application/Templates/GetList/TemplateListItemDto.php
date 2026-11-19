<?php

namespace Src\Application\Templates\GetList;

use DateTimeImmutable;

class TemplateListItemDto
{
    /**
     * @param  string[]|null  $tags  null, если файл шаблона не удалось прочитать
     */
    public function __construct(
        public int $id,
        public string $name,
        public DateTimeImmutable $createdAt,
        public ?array $tags,
    ) {}
}
