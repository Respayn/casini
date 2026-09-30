<?php

namespace App\Livewire;

use App\Data\Sidebar\EmployeeData;
use App\Services\SidebarService;
use App\Support\SidebarProjectAccess;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class Sidebar extends Component
{
    /** @var array<int, EmployeeData> */
    public array $employees = [];

    public array $sortOptions = [];

    public ?string $sortBy = null;

    public string $searchQuery = '';

    #[Session(key: SidebarProjectAccess::SESSION_KEY)]
    public ?int $selectedProjectId = null;

    private SidebarService $sidebarService;

    public function boot(SidebarService $sidebarService): void
    {
        $this->sidebarService = $sidebarService;
    }

    public function mount(): void
    {
        $this->sanitizeSelectedProjectFromSession();
        $this->sortOptions = $this->sidebarService->getRoleOptions();
        $this->sortBy = $this->sortOptions[0]['value'] ?? null;
        $this->getEmployees();
    }

    public function updatedSortBy(): void
    {
        $this->getEmployees();
    }

    public function updatedSearchQuery(): void
    {
        $this->getEmployees();
    }

    public function selectProject(int $projectId): void
    {
        if ($this->selectedProjectId === $projectId) {
            $this->resetSelectedProject();

            return;
        }

        if (! SidebarProjectAccess::userCanAccessProject($projectId)) {
            return;
        }

        $this->selectedProjectId = $projectId;
        $this->dispatch('sidebar-project-selected', projectId: $projectId);
    }

    public function resetSelectedProject(): void
    {
        $this->selectedProjectId = null;
        $this->dispatch('sidebar-project-cleared');
    }

    public function clearFilters(): void
    {
        $this->searchQuery = '';
        $this->selectedProjectId = null;
        $this->dispatch('sidebar-project-cleared');

        // Пересобираем дерево, чтобы свернуть раскрытых менеджера/клиента.
        $this->getEmployees();
    }

    #[Computed]
    public function canClearFilters(): bool
    {
        return $this->selectedProjectId !== null || $this->searchQuery !== '';
    }

    #[On('sidebar-project-cleared')]
    public function syncClearedSelection(): void
    {
        $this->selectedProjectId = null;
    }

    private function sanitizeSelectedProjectFromSession(): void
    {
        if ($this->selectedProjectId === null) {
            return;
        }

        if (! SidebarProjectAccess::userCanAccessProject($this->selectedProjectId)) {
            $this->selectedProjectId = null;
        }
    }

    private function getEmployees(): void
    {
        $this->employees = $this->sidebarService->getEmployees($this->sortBy, $this->searchQuery);
        $this->expandSelectedProjectPath();

        if ($this->searchQuery === '') {
            return;
        }

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
                    $client->open = true;
                }
            }
            if (Str::contains(Str::lower($employee->name), Str::lower($this->searchQuery))) {
                $employee->open = true;
            }
        }
        unset($employee, $client);
    }

    private function expandSelectedProjectPath(): void
    {
        if ($this->selectedProjectId === null) {
            return;
        }

        foreach ($this->employees as $employee) {
            foreach ($employee->clients as $client) {
                foreach ($client->projects as $project) {
                    if ($project->id !== $this->selectedProjectId) {
                        continue;
                    }

                    $employee->open = true;
                    $client->open = true;

                    return;
                }
            }
        }
    }

    public function render(): View
    {
        return view('livewire.sidebar');
    }
}
