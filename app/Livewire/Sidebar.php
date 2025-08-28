<?php

namespace App\Livewire;

use App\Data\Sidebar\EmployeeData;
use App\Services\SidebarService;
use Livewire\Component;
use Str;

class Sidebar extends Component
{
    /** @var array<int, EmployeeData> */
    public array $employees;
    public array $sortOptions = [];
    public ?string $sortBy = 'manager';
    public string $searchQuery = '';
    public ?int $selectedProjectId = null;

    private SidebarService $sidebarService;

    public function boot(SidebarService $sidebarService)
    {
        $this->sidebarService = $sidebarService;
    }

    public function mount()
    {
        $this->getEmployees();
        $this->sortOptions = $this->sidebarService->getRoleOptions();
    }

    public function updatedSortBy()
    {
        $this->getEmployees();
    }

    public function updatedSearchQuery()
    {
        $this->getEmployees();
    }

    public function resetSelectedProject()
    {
        $this->selectedProjectId = null;
    }

    private function getEmployees()
    {
        $this->employees = $this->sidebarService->getEmployees($this->sortBy, $this->searchQuery);

        foreach ($this->employees as &$employee) {
            foreach ($employee->clients as &$client) {
                foreach ($client->projects as $project) {
                    if (Str::contains(Str::lower($project->name), Str::lower($this->searchQuery))) {
                        $employee->open = true;
                        $client->open = true;
                    }
                }
                if (Str::contains(Str::lower($client->name), Str::lower($this->searchQuery))) {
                    $employee->open = true;
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.sidebar');
    }
}
