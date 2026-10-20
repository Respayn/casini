<?php

namespace Src\Domain\Templates;

enum ReportTagKind: string
{
    case Text = 'text';
    case Image = 'image';
    case Table = 'table';
    case List = 'list';

    public function parameterLabel(): string
    {
        return match ($this) {
            self::Text => 'текст',
            self::Image => 'изображение',
            self::Table => 'таблица',
            self::List => 'список, строка на каждую работу',
        };
    }
}
