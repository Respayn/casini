<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateClientProjectForm extends Form
{
    public ?int $id = null;
    #[Validate('required|boolean', message: 'Статус клиенто-проекта обязателен')]
    public bool $isActive = false;

    #[Validate('required|string|max:255', message: 'Название клиенто-проекта обязательно')]
    public string $name = '';

    #[Validate('required|exists:clients,id', message: 'Выберите клиента')]
    public int $client;

    #[Validate('required|url|max:255', message: 'Введите корректный URL-адрес')]
    public string $domain;

    #[Validate('nullable|exists:users,id', message: 'Укажите менеджера')]
    public ?int $manager = null;

    // TODO: required (когда появится пользователь)
    #[Validate('nullable|exists:users,id', message: 'Укажите специалиста')]
    public int $specialist;

    #[Validate('array', message: 'Помощники должны быть массивом')]
    public array $assistants = [];

    #[Validate('required|string|max:255', message: 'KPI обязателен')]
    public string $kpi = '';

    #[Validate('required|string|max:255', message: 'Тип клиенто-проекта обязателен')]
    public string $projectType = '';

    #[Validate('nullable|string|max:255')]
    public ?string $isInternal = null;

    #[Validate('required|array', message: 'Выберите хотя бы один регион продвижения')]
    public array $promotionRegions = [];

    #[Validate('required|array', message: 'Выберите хотя бы одну тематику продвижения')]
    public array $promotionTopics = [];
}
