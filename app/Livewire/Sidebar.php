<?php

namespace App\Livewire;

use App\Data\Sidebar\EmployeeData;
use App\Services\SidebarService;
use App\Support\SidebarProjectContext;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    /**
     * Минимальное время показа скелетона при поиске / смене роли (мкс).
     * Без паузы запрос слишком быстрый — скелетон не успевает отрисоваться.
     */
    private const TREE_LOADING_HOLD_MICROSECONDS = 550_000;

    /** @var array<int, EmployeeData> */
    public array $employees = [];

    public array $sortOptions = [];

    public ?string $sortBy = null;

    public string $searchQuery = '';

    public ?int $selectedProjectId = null;

    private SidebarService $sidebarService;

    private SidebarProjectContext $projectContext;

    public function boot(SidebarService $sidebarService, SidebarProjectContext $projectContext): void
    {
        $this->sidebarService = $sidebarService;
        $this->projectContext = $projectContext;
    }

    public function mount(): void
    {
        $this->sortOptions = $this->sidebarService->getRoleOptions();
        $this->sortBy = $this->sortOptions[0]['value'] ?? null;
        $this->selectedProjectId = $this->projectContext->get();
        $this->getEmployees();
    }

    public function updatedSortBy(): void
    {
        // Держим запрос чуть дольше, чтобы скелетон успел отрисоваться (поиск сам по себе слишком быстрый).
        usleep(self::TREE_LOADING_HOLD_MICROSECONDS);
        $this->getEmployees();
    }

    public function updatedSearchQuery(): void
    {
        usleep(self::TREE_LOADING_HOLD_MICROSECONDS);
        $this->getEmployees();
    }

    public function selectProject(int $projectId): void
    {
        if ($this->selectedProjectId === $projectId) {
            $this->resetSelectedProject();

            return;
        }

        if (! $this->projectContext->set($projectId)) {
            return;
        }

        $this->selectedProjectId = $projectId;
        $this->dispatch('sidebar-project-selected', projectId: $projectId);
    }

    public function resetSelectedProject(): void
    {
        $this->projectContext->clear();
        $this->selectedProjectId = null;
        $this->dispatch('sidebar-project-cleared');
    }

    public function clearFilters(): void
    {
        $this->searchQuery = '';
        $this->projectContext->clear();
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
