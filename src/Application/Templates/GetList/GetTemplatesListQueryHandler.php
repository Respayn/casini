<?php

namespace Src\Application\Templates\GetList;

use Src\Application\Templates\TemplateTagReaderInterface;
use Src\Domain\Templates\Template;
use Src\Domain\Templates\TemplateRepositoryInterface;

class GetTemplatesListQueryHandler
{
    private readonly TemplateRepositoryInterface $templateRepository;

    private readonly TemplateTagReaderInterface $tagReader;

    public function __construct(TemplateRepositoryInterface $templateRepository, TemplateTagReaderInterface $tagReader)
    {
        $this->templateRepository = $templateRepository;
        $this->tagReader = $tagReader;
    }

    /**
     * @return TemplateListItemDto[]
     */
    public function handle(GetTemplatesListQuery $query): array
    {
        return array_map(
            fn (Template $template): TemplateListItemDto => new TemplateListItemDto(
                $template->getId(),
                $template->getName(),
                $template->getCreatedAt(),
                $this->tagReader->read($template->getPath()),
            ),
            $this->templateRepository->findAll(),
        );
    }
}
