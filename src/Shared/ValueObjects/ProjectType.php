<?php

namespace Src\Shared\ValueObjects;

enum ProjectType: string
{
    case CONTEXT_AD = 'context_ad';
    case SEO_PROMOTION = 'seo_promotion';

    public function label(): string
    {
        return match ($this) {
            self::CONTEXT_AD => 'Контекстная реклама',
            self::SEO_PROMOTION => 'SEO-продвижение',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::CONTEXT_AD => 'Контекст',
            self::SEO_PROMOTION => 'SEO',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(ProjectType $projectType) => ['label' => $projectType->label(), 'value' => $projectType->value],
            self::cases()
        );
    }
}
