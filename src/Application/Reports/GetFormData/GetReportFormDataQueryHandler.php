<?php

namespace Src\Application\Reports\GetFormData;

use App\Models\Project;
use App\Models\User;
use App\Support\ClientProjectVisibility;
use Src\Domain\Templates\TemplateRepositoryInterface;

class GetReportFormDataQueryHandler
{
    public function __construct(
        private readonly TemplateRepositoryInterface $templateRepository,
    ) {}

    public function handle(GetReportFormDataQuery $query): ReportFormDataResponse
    {
        $user = $query->userId !== null
            ? User::query()->find($query->userId)
            : null;

        $projectsQuery = Project::query()->orderBy('name');
        ClientProjectVisibility::restrictProjectQuery($projectsQuery, $user);

        $projectsData = $projectsQuery
            ->get(['id', 'name'])
            ->map(static fn (Project $project): array => [
                'value' => $project->id,
                'label' => $project->name,
            ])
            ->all();

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
