<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case CA_SPECIALIST = 'kr';
    case SEO_SPECIALIST = 'seo';
    case SEO_DEPARTMENT_HEAD = 'rucovotdelseo';
    case CA_DEPARTMENT_HEAD = 'rucovotdelkp';
    case MANAGER_DEPARTMENT_HEAD = 'rucovotdelmanager';
    case OFFICE_MANAGER = 'office_manager';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            static::ADMIN => 'Администратор',
            static::MANAGER => 'Менеджер',
            static::CA_SPECIALIST => 'Специалист (директолог)',
            static::SEO_SPECIALIST => 'Специалист (SEO)',
            static::SEO_DEPARTMENT_HEAD => 'Руководитель SEO отдела',
            static::CA_DEPARTMENT_HEAD => 'Руководитель KP отдела',
            static::MANAGER_DEPARTMENT_HEAD => 'Руководитель отдела менеджеров',
            static::OFFICE_MANAGER => 'Офис-менеджер',
        };
    }
}
