<?php

namespace Src\Application\Reports\GetFormData;

use App\Models\User;
use App\Support\SidebarProjectAccess;
use Src\Domain\Projects\ProjectRepositoryInterface;
use Src\Domain\Templates\TemplateRepositoryInterface;

class GetReportFormDataQueryHandler
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectsRepository,
        private readonly TemplateRepositoryInterface $templateRepository,
    ) {}

    public function handle(GetReportFormDataQuery $query): ReportFormDataResponse
    {
        $user = $query->userId !== null
            ? User::query()->find($query->userId)
            : null;

        $projects = $this->projectsRepository->findAll();
        $projectsData = [];

        foreach ($projects as $project) {
            if ($user !== null && ! SidebarProjectAccess::userCanAccessProject($project->getId(), $user)) {
                continue;
            }

            $projectsData[] = [
                'value' => $project->getId(),
                'label' => $project->getName(),
            ];
        }

        usort($projectsData, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        $formats = [
            ['value' => 'docx', 'label' => 'DOCX'],
        ];

        $templates = $this->templateRepository->findAll();
        $templatesData = array_map(function ($template) {
            return [
                'value' => $template->getId(),
                'label' => $template->getName(),
            ];
        }, $templates);

        return new ReportFormDataResponse($projectsData, $formats, $templatesData);
    }
}
