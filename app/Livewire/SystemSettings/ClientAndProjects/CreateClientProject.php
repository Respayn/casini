<?php

namespace App\Livewire\SystemSettings\ClientAndProjects;

use App\Livewire\Forms\SystemSettings\ClientAndProjects\CreateClientProjectForm;
use App\Services\DepartmentService;
use Illuminate\Support\Collection;
use Livewire\Component;

class CreateClientProject extends Component
{
    public CreateClientProjectForm $clientProjectForm;

    private DepartmentService $departmentService;

    public Collection $departments;

    public function boot(
        DepartmentService $departmentService
    )
    {
        $this->departmentService = $departmentService;
    }

    public function mount()
    {
        $this->departments = $this->departmentService->getDepartments();
    }

    public function render()
    {
        return view('livewire.system-settings.client-and-porjects.create-client-project');
    }

    public function save()
    {
        $this->validate();

        // Если валидация пройдена, можно выполнить логику сохранения данных
        dd('Данные успешно сохранены!', $this->clientProjectForm);
    }
}
