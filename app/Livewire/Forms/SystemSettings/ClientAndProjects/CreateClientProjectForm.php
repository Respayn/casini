<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use App\Data\ProjectData;
use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Component;
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
    public ?int $specialist = null;

    #[Validate('array', message: 'Помощники должны быть массивом')]
    public array $assistants = [];

    #[Validate('required|string|max:255', message: 'KPI обязателен')]
    public string $kpi = '';

    #[Validate('required|string|max:255', message: 'Тип клиенто-проекта обязателен')]
    public string $projectType = '';

    #[Validate('nullable|string|max:255')]
    public ?bool $isInternal = null;

    #[Validate('required|array', message: 'Выберите хотя бы один регион продвижения')]
    public array $promotionRegions = [];

    #[Validate('required|array', message: 'Выберите хотя бы одну тематику продвижения')]
    public array $promotionTopics = [];

    public function rules()
    {
        return array_merge(
            [
                'isActive' => 'required|boolean',
                'name' => 'required|string|max:255',
                'client' => 'required|exists:clients,id',
                'domain' => 'required|url|max:255',
                'manager' => 'nullable|exists:users,id',
                'specialist' => 'nullable|exists:users,id',
                'assistants' => 'array',
                'kpi' => 'required|string|max:255',
                'projectType' => 'required|string|max:255',
                'isInternal' => 'nullable|string|max:255',
                'promotionRegions' => 'required|array',
                'promotionTopics' => 'required|array',
            ],
        );
    }

    /**
     * Метод для заполнения данных формы из модели проекта.
     *
     * @param ProjectData $project
     * @return void
     */
    public function from($project)
    {
        $this->id = $project->id;
        $this->name = $project->name;
        $this->domain = $project->domain;
        $this->client = $project->client_id;
        $this->specialist = $project->specialist_id;
        $this->projectType = $project->project_type->value;
        $this->kpi = $project->kpi;
        $this->isActive = $project->is_active;
        $this->isInternal = $project->is_internal;
        $this->promotionRegions = $project->promotionRegions->toArray();
        $this->promotionTopics = $project->promotionTopics->toArray();
    }
}
