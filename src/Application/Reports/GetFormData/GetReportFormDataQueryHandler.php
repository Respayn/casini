<?php

namespace Src\Application\Reports\GetFormData;

use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Templates\TemplateRepositoryInterface;

class GetReportFormDataQueryHandler
{
    private readonly ProjectRepositoryInterface $projectsRepository;
    private readonly TemplateRepositoryInterface $templateRepository;

    public function __construct(ProjectRepositoryInterface $projectRepository, TemplateRepositoryInterface $templateRepository)
    {
        $this->projectsRepository = $projectRepository;
        $this->templateRepository = $templateRepository;
    }

    public function handle(GetReportFormDataQuery $query): ReportFormDataResponse
    {
        $projects = $this->projectsRepository->findAll();
        $projectsData = array_map(function($project) {
            return [
                'value' => $project->getId(),
                'label' => $project->getName()
            ];
        }, $projects);
        usort($projectsData, function($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        $formats = [
            ['value' => 'docx', 'label' => 'DOCX']
        ];

        $templates = $this->templateRepository->findAll();
        $templatesData = array_map(function($template) {
            return [
                'value' => $template->getId(),
                'label' => $template->getName()
            ];
        }, $templates);

        return new ReportFormDataResponse($projectsData, $formats, $templatesData);
    }
}
