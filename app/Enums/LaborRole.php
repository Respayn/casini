<?php

namespace App\Enums;

/**
 * Роль, в колонку которой в Каналах попадают часы сотрудника. Значение = ключ колонки отчёта.
 */
enum LaborRole: string
{
    case SeoAssistant = 'seo-assistant';
    case SeoSpecialist = 'seo-specialist';
    case Analyst = 'analyst';
    case OrkManager = 'ork-manager';
}
