<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateClientProjectForm extends Form
{
    #[Validate('required|boolean', message: 'Статус клиенто-проекта обязателен')]
    public bool $status = false;

    #[Validate('required|string|max:255', message: 'Название клиенто-проекта обязательно')]
    public string $name = '';

    #[Validate('required|exists:clients,id', message: 'Выберите клиента')]
    public int $client;

    #[Validate('nullable|url|max:255', message: 'Введите корректный URL-адрес')]
    public ?string $url = null;

    #[Validate('nullable|exists:users,id', message: 'Укажите менеджера')]
    public ?int $manager = null;

    #[Validate('required|exists:users,id', message: 'Укажите специалиста')]
    public int $specialist;

    #[Validate('array', message: 'Помощники должны быть массивом')]
    public array $assistants = [];

    #[Validate('required|string|max:255', message: 'KPI обязателен')]
    public string $kpi = '';

    #[Validate('required|string|max:255', message: 'Отдел обязателен')]
    public string $department = '';

    #[Validate('required|string|max:255', message: 'Тип клиенто-проекта обязателен')]
    public string $type = '';

    #[Validate('nullable|string|max:255')]
    public ?string $ownerStatus = null;

    #[Validate('required|array', message: 'Выберите хотя бы один регион продвижения')]
    public array $promotionRegions = [];

    #[Validate('required|array', message: 'Выберите хотя бы одну тематику продвижения')]
    public array $promotionTopics = [];
}
