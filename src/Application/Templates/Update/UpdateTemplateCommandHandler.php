<?php

namespace Src\Application\Templates\Update;

use Illuminate\Support\Facades\Storage;
use Src\Domain\Templates\TemplateRepositoryInterface;

class UpdateTemplateCommandHandler
{
    public function __construct(
        private readonly TemplateRepositoryInterface $templateRepository,
    ) {}

    public function handle(UpdateTemplateCommand $command): void
    {
        $template = $this->templateRepository->findById($command->templateId);
        $oldPath = $template->getPath();

        $template->rename($command->name);

        if ($command->file !== null) {
            $template->replacePath($command->file->store('templates'));
        }

        $this->templateRepository->save($template);

        if ($command->file !== null && $oldPath !== $template->getPath()) {
            Storage::delete($oldPath);
        }
    }
}
