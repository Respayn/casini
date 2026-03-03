<?php

namespace Src\Application\Templates\GetList;

use Src\Domain\Templates\TemplateRepositoryInterface;

class GetTemplatesListQueryHandler
{
    private readonly TemplateRepositoryInterface $templateRepository;

    public function __construct(TemplateRepositoryInterface $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function handle(GetTemplatesListQuery $query): array
    {
        return $this->templateRepository->findAll();
    }
}
