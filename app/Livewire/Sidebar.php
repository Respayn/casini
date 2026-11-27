<?php

namespace App\Livewire;

use App\Data\Sidebar\EmployeeData;
use App\Services\IntegrationSync\IntegrationManualRefreshProgress;
use App\Services\SidebarService;
use App\Support\SidebarProjectAccess;
use Illuminate\Support\Facades\Auth;
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

    /** Ручной съём Каналов/Статистики: нельзя менять фильтр клиенто-проекта. */
    public bool $manualRefreshLocked = false;

    private SidebarService $sidebarService;

    private IntegrationManualRefreshProgress $manualRefreshProgress;

    public function boot(
        SidebarService $sidebarService,
        IntegrationManualRefreshProgress $manualRefreshProgress,
    ): void {
        $this->sidebarService = $sidebarService;
        $this->manualRefreshProgress = $manualRefreshProgress;
    }

    public function mount(): void
    {
        $this->sanitizeSelectedProjectFromSession();
        $this->sortOptions = $this->sidebarService->getRoleOptions();
        $this->sortBy = $this->sortOptions[0]['value'] ?? null;
        $this->syncManualRefreshLock();
        $this->getEmployees();
    }

    public function pollManualRefreshLock(): void
    {
        $this->syncManualRefreshLock();
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
        if ($this->manualRefreshLocked) {
            return;
        }

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
        if ($this->manualRefreshLocked) {
            return;
        }

        $this->selectedProjectId = null;
        $this->dispatch('sidebar-project-cleared');
    }

    public function clearFilters(): void
    {
        if ($this->manualRefreshLocked) {
            return;
        }

        $this->searchQuery = '';
        $this->selectedProjectId = null;
        $this->dispatch('sidebar-project-cleared');

        // Пересобираем дерево, чтобы свернуть раскрытых менеджера/клиента.
        $this->getEmployees();
    }

    #[Computed]
    public function canClearFilters(): bool
    {
        if ($this->manualRefreshLocked) {
            return false;
        }

        return $this->selectedProjectId !== null || $this->searchQuery !== '';
    }

    private function syncManualRefreshLock(): void
    {
        $agencyId = $this->currentAgencyId();
        $this->manualRefreshLocked = $agencyId !== null
            && $this->manualRefreshProgress->isRunning($agencyId);
    }

    private function currentAgencyId(): ?int
    {
        $sessionAgencyId = session('current_agency_id');
        if (filled($sessionAgencyId) && (int) $sessionAgencyId > 0) {
            return (int) $sessionAgencyId;
        }

        $fallback = Auth::user()?->agencies()->value('agencies.id');

        return filled($fallback) && (int) $fallback > 0
            ? (int) $fallback
            : null;
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
