<?php

namespace App\Enums;

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
}
