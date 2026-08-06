<?php

namespace App\Livewire\Concerns;

use App\Models\Project;
use App\Support\SidebarProjectContext;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

trait WithSidebarProjectFilter
{
    public ?int $sidebarProjectId = null;

    public function mountWithSidebarProjectFilter(SidebarProjectContext $context): void
    {
        $this->sidebarProjectId = $context->get();
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

    public function clearSidebarProjectFilter(SidebarProjectContext $context): void
    {
        $context->clear();
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

    protected function afterSidebarProjectFilterChanged(): void
    {
        //
    }
}
