<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Src\Application\Templates\Create\CreateTemplateCommand;
use Src\Application\Templates\Create\CreateTemplateCommandHandler;
use Src\Application\Templates\Delete\DeleteTemplateCommand;
use Src\Application\Templates\Delete\DeleteTemplateCommandHandler;
use Src\Application\Templates\Download\DownloadTemplateFileQuery;
use Src\Application\Templates\Download\DownloadTemplateFileQueryHandler;
use Src\Application\Templates\GetList\GetTemplatesListQuery;
use Src\Application\Templates\GetList\GetTemplatesListQueryHandler;
use Src\Domain\Templates\TemplateInUseException;

new
    #[Title('Шаблоны отчетов - Casini')]
    class extends Component
    {
        use WithFileUploads;

        public $newTemplate;

        #[Computed]
        public function canEditTemplates(): bool
        {
            $user = auth()->user();

            return $user !== null
                && ($user->can('edit report templates') || $user->can('full report templates'));
        }

        #[Computed]
        public function canDeleteTemplates(): bool
        {
            $user = auth()->user();

            return $user !== null && $user->can('full report templates');
        }

        #[Computed]
        public function canCreateReports(): bool
        {
            $user = auth()->user();

            return $user !== null
                && ($user->can('read reports') || $user->can('full reports'));
        }

        public function updatedNewTemplate()
        {
            $this->ensureCanEditTemplates();
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

            $this->newTemplate = null;
        }

        #[Computed]
        public function templates()
        {
            return app(GetTemplatesListQueryHandler::class)->handle(
                new GetTemplatesListQuery
            );
        }

        public function download(DownloadTemplateFileQueryHandler $query, int $templateId)
        {
            $file = $query->handle(
                new DownloadTemplateFileQuery($templateId)
            );

            return response()->download($file->path, $file->name);
        }

        public function delete(DeleteTemplateCommandHandler $command, int $templateId)
        {
            $this->ensureCanDeleteTemplates();
            $this->resetErrorBag();

            try {
                $command->handle(new DeleteTemplateCommand($templateId));
            } catch (TemplateInUseException $e) {
                $this->addError('delete', $e->getMessage());
            }
        }

        private function ensureCanEditTemplates(): void
        {
            abort_unless($this->canEditTemplates, 403, __('permissions.denied'));
        }

        private function ensureCanDeleteTemplates(): void
        {
            abort_unless($this->canDeleteTemplates, 403, __('permissions.denied'));
        }
    };
