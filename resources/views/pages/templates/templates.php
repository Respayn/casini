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

        public function updatedNewTemplate()
        {
            $this->validate([
                'newTemplate' => 'required|file|mimes:docx'
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
                new GetTemplatesListQuery()
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
            try {
                $command->handle(new DeleteTemplateCommand($templateId));
            } catch (TemplateInUseException $e) {
                $this->addError('delete', $e->getMessage());
            }
        }
    };
