<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Application\Templates\Create\CreateTemplateCommand;
use Src\Application\Templates\Create\CreateTemplateCommandHandler;
use Src\Application\Templates\Update\UpdateTemplateCommand;
use Src\Application\Templates\Update\UpdateTemplateCommandHandler;
use Src\Domain\Templates\ReportTag;
use Src\Domain\Templates\ReportTagCatalog;
use Src\Domain\Templates\Template;
use Src\Domain\Templates\TemplateRepositoryInterface;

new
#[Title('Создание шаблона - Casini')]
class extends Component
{
    use WithFileUploads;

    public $newTemplate;

    public string $templateName = '';

    public int $step = 1;

    public bool $showTags = false;

    public ?int $editingTemplateId = null;

    public ?string $existingFileName = null;

    public function mount(?int $templateId = null): void
    {
        $this->ensureCanEditTemplates();

        if ($templateId === null) {
            return;
        }

        $template = app(TemplateRepositoryInterface::class)->findById($templateId);
        $this->editingTemplateId = $template->getId();
        $this->templateName = $template->getName();
        $this->existingFileName = $this->fileLabel($template);
        $this->step = 2;
    }

    /**
     * @return list<ReportTag>
     */
    #[Computed]
    public function allTags(): array
    {
        return ReportTagCatalog::all();
    }

    #[Computed]
    public function canSaveTemplate(): bool
    {
        return filled(trim($this->templateName))
            && $this->hasSelectedFile()
            && ! $this->getErrorBag()->has('newTemplate');
    }

    #[Computed]
    public function selectedFileName(): ?string
    {
        if ($this->newTemplate !== null) {
            return $this->newTemplate->getClientOriginalName();
        }

        return $this->existingFileName;
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->editingTemplateId !== null;
    }

    public function goToStepTwo(): void
    {
        $this->ensureCanEditTemplates();
        $this->step = 2;
        $this->resetErrorBag();
    }

    public function updatedTemplateName(): void
    {
        $this->resetErrorBag('templateName');
    }

    public function updatedNewTemplate(): void
    {
        $this->ensureCanEditTemplates();
        $this->step = 2;
        $this->resetErrorBag('newTemplate');

        $this->validateOnly('newTemplate', $this->templateFileRules(), $this->templateFileMessages());
    }

    public function deleteTemplateFile(): void
    {
        $this->ensureCanEditTemplates();

        if ($this->newTemplate !== null) {
            $this->reset('newTemplate');
            $this->resetErrorBag('newTemplate');

            return;
        }

        $this->existingFileName = null;
        $this->resetErrorBag('newTemplate');
    }

    public function saveTemplate(): void
    {
        $this->ensureCanEditTemplates();
        $this->step = 2;

        $this->templateName = trim($this->templateName);

        $this->validate($this->templateSaveRules(), $this->templateSaveMessages());

        if ($this->editingTemplateId !== null) {
            app(UpdateTemplateCommandHandler::class)->handle(
                new UpdateTemplateCommand(
                    $this->editingTemplateId,
                    $this->templateName,
                    $this->newTemplate,
                )
            );
        } else {
            app(CreateTemplateCommandHandler::class)->handle(
                new CreateTemplateCommand($this->newTemplate, $this->templateName)
            );
        }

        $this->redirect(route('reports.templates'), navigate: true);
    }

    /**
     * @return array<string, string>
     */
    private function templateFileRules(): array
    {
        return [
            'newTemplate' => 'required|file|mimes:docx',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function templateSaveRules(): array
    {
        $fileRule = $this->existingFileName
            ? 'nullable|file|mimes:docx'
            : 'required|file|mimes:docx';

        return [
            'templateName' => 'required|string|max:255',
            'newTemplate' => $fileRule,
        ];
    }

    private function hasSelectedFile(): bool
    {
        return $this->newTemplate !== null || filled($this->existingFileName);
    }

    private function fileLabel(Template $template): string
    {
        $name = $template->getName();

        if (str_ends_with(mb_strtolower($name), '.docx')) {
            return $name;
        }

        return $name.'.docx';
    }

    /**
     * @return array<string, string>
     */
    private function templateFileMessages(): array
    {
        return [
            'newTemplate.required' => 'Выберите файл шаблона.',
            'newTemplate.file' => 'Не удалось загрузить файл шаблона.',
            'newTemplate.mimes' => 'Шаблон должен быть файлом Word в формате .docx.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function templateSaveMessages(): array
    {
        return [
            'templateName.required' => 'Укажите название шаблона.',
            'templateName.max' => 'Название шаблона слишком длинное.',
            ...$this->templateFileMessages(),
        ];
    }

    private function ensureCanEditTemplates(): void
    {
        $user = auth()->user();
        $canEdit = $user !== null
            && ($user->can('edit report templates') || $user->can('full report templates'));

        abort_unless($canEdit, 403, __('permissions.denied'));
    }
};
