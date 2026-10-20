<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Application\Templates\Create\CreateTemplateCommand;
use Src\Application\Templates\Create\CreateTemplateCommandHandler;
use Src\Domain\Templates\ReportTag;
use Src\Domain\Templates\ReportTagCatalog;

new
#[Title('Создание шаблона - Casini')]
class extends Component
{
    use WithFileUploads;

    public $newTemplate;

    public int $step = 1;

    public bool $showTags = false;

    public function mount(): void
    {
        $this->ensureCanEditTemplates();
    }

    /**
     * @return list<ReportTag>
     */
    #[Computed]
    public function allTags(): array
    {
        return ReportTagCatalog::all();
    }

    public function updatedNewTemplate(): void
    {
        $this->ensureCanEditTemplates();
        $this->step = 2;
        $this->resetErrorBag();

        $this->validate([
            'newTemplate' => 'required|file|mimes:docx',
        ], [
            'newTemplate.required' => 'Выберите файл шаблона.',
            'newTemplate.file' => 'Не удалось загрузить файл шаблона.',
            'newTemplate.mimes' => 'Шаблон должен быть файлом Word в формате .docx.',
        ]);

        app(CreateTemplateCommandHandler::class)->handle(
            new CreateTemplateCommand($this->newTemplate)
        );

        $this->redirect(route('reports.templates'), navigate: true);
    }

    private function ensureCanEditTemplates(): void
    {
        $user = auth()->user();
        $canEdit = $user !== null
            && ($user->can('edit report templates') || $user->can('full report templates'));

        abort_unless($canEdit, 403, __('permissions.denied'));
    }
};
