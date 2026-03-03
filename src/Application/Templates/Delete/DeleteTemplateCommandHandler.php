<?php

namespace Src\Application\Templates\Delete;

use Src\Domain\Templates\TemplateRepositoryInterface;

class DeleteTemplateCommandHandler
{
    private readonly TemplateRepositoryInterface $templateRepository;

    public function __construct(TemplateRepositoryInterface $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function handle(DeleteTemplateCommand $command)
    {
        $template = $this->templateRepository->findById($command->templateId);

        // TODO: check for template existence

        $this->templateRepository->remove($template);
    }
}