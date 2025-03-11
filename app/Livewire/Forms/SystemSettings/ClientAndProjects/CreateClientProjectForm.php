<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

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
    public int $specialist;

    #[Validate('array', message: 'Помощники должны быть массивом')]
    public array $assistants = [];

    #[Validate('required|string|max:255', message: 'KPI обязателен')]
    public string $kpi = '';

    #[Validate('required|string|max:255', message: 'Отдел обязателен')]
    public string $department = '';

    #[Validate('required|string|max:255', message: 'Тип клиенто-проекта обязателен')]
    public string $projectType = '';

    #[Validate('nullable|string|max:255')]
    public ?string $isInternal = null;

    #[Validate('required|array', message: 'Выберите хотя бы один регион продвижения')]
    public array $promotionRegions = [];

    #[Validate('required|array', message: 'Выберите хотя бы одну тематику продвижения')]
    public array $promotionTopics = [];

    public ProjectBonusGuaranteeForm $bonusGuaranteeForm;

    public function __construct(Component $component, $propertyName)
    {
        parent::__construct($component, $propertyName);

        $this->bonusGuaranteeForm = new ProjectBonusGuaranteeForm($component, $propertyName);
    }

    public function rules()
    {
        return array_merge(
            [
                'is_active' => 'required|boolean',
                'name' => 'required|string|max:255',
                'client' => 'required|exists:clients,id',
                'domain' => 'required|url|max:255',
                'manager' => 'nullable|exists:users,id',
                'specialist' => 'nullable|exists:users,id',
                'assistants' => 'array',
                'kpi' => 'required|string|max:255',
                'department' => 'required|string|max:255',
                'projectType' => 'required|string|max:255',
                'is_internal' => 'nullable|string|max:255',
                'promotionRegions' => 'required|array',
                'promotionTopics' => 'required|array',
            ],

            $this->bonusGuaranteeForm->prefixedRules('bonusGuaranteeForm.')
        );
    }

    /**
     * Метод для заполнения данных формы из модели проекта.
     *
     * @param $project
     * @return void
     */
    public function fillFromModel($project)
    {
        $this->id = $project->id;
        $this->name = $project->name;
        $this->domain = $project->domain;
        $this->client = $project->client_id;
        $this->specialist = $project->specialist_id;
        $this->manager = $project->manager_id;
        $this->department = $project->department_id;
        $this->projectType = $project->project_type;
        $this->kpi = $project->kpi;
        $this->is_internal = $project->is_internal;
        $this->is_active = $project->is_active;

        $this->assistants = $project->assistants->pluck('id')->toArray();
        $this->promotionRegions = $project->promotionRegions->pluck('id')->toArray();
        $this->promotionTopics = $project->promotionTopics->pluck('id')->toArray();

        if ($project->bonusCondition) {
            $this->bonusGuaranteeForm->fillFromModel($project->bonusCondition);
        }
    }
}
