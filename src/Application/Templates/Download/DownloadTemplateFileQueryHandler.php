<?php

namespace Src\Application\Templates\Download;

use Illuminate\Support\Facades\Storage;
use Src\Domain\Templates\TemplateRepositoryInterface;

class DownloadTemplateFileQueryHandler
{
    private readonly TemplateRepositoryInterface $templateRepository;

    public function __construct(TemplateRepositoryInterface $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function handle(DownloadTemplateFileQuery $query): DownloadTemplateFileDto
    {
        $template = $this->templateRepository->findById($query->templateId);
        
        return new DownloadTemplateFileDto(
            Storage::path($template->getPath()),
            $template->getName()
        );
    }
}
