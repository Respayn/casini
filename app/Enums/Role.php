<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case DEFAULT = 'default';
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
            self::ADMIN => 'Администратор',
            self::DEFAULT => 'Роль по умолчанию',
            self::MANAGER => 'Менеджер',
            self::CA_SPECIALIST => 'Специалист (директолог)',
            self::SEO_SPECIALIST => 'Специалист (SEO)',
            self::SEO_DEPARTMENT_HEAD => 'Руководитель SEO отдела',
            self::CA_DEPARTMENT_HEAD => 'Руководитель KP отдела',
            self::MANAGER_DEPARTMENT_HEAD => 'Руководитель отдела менеджеров',
            self::OFFICE_MANAGER => 'Офис-менеджер',
        };
    }
}
