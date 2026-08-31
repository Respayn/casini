<?php

namespace App\Livewire\Concerns;

use App\Models\Project;
use App\Support\SidebarProjectAccess;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;

trait WithSidebarProjectFilter
{
    #[Session(key: SidebarProjectAccess::SESSION_KEY)]
    public ?int $sidebarProjectId = null;

    public function bootWithSidebarProjectFilter(): void
    {
        $this->sanitizeSidebarProjectIdFromSession();
    }

    #[On('sidebar-project-selected')]
    public function onSidebarProjectSelected(int $projectId): void
    {
        $this->sidebarProjectId = $projectId;
        $this->afterSidebarProjectFilterChanged();
    }

    #[On('sidebar-project-cleared')]
    public function onSidebarProjectCleared(): void
    {
        $this->sidebarProjectId = null;
        $this->afterSidebarProjectFilterChanged();
    }

    public function clearSidebarProjectFilter(): void
    {
        $this->sidebarProjectId = null;
        $this->dispatch('sidebar-project-cleared');
        $this->afterSidebarProjectFilterChanged();
    }

    /**
     * @return array{id: int, name: string}|null
     */
    #[Computed]
    public function sidebarFilteredProject(): ?array
    {
        if ($this->sidebarProjectId === null) {
            return null;
        }

        $project = Project::query()->find($this->sidebarProjectId);

        if ($project === null) {
            return null;
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
        ];
    }

    protected function sanitizeSidebarProjectIdFromSession(): void
    {
        if ($this->sidebarProjectId === null) {
            return;
        }

        if (! SidebarProjectAccess::userCanAccessProject($this->sidebarProjectId)) {
            $this->sidebarProjectId = null;
        }
    }

    protected function afterSidebarProjectFilterChanged(): void
    {
        //
    }
}
