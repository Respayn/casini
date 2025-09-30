<?php

namespace App\Repositories;

use App\Data\Accounting\CompanyData;
use App\Exceptions\ProjectNotFoundException;
use App\Helpers\StringHelper;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

class ProjectRepository
{
    public function all()
    {
        return Project::with('bonusCondition')->get();
    }

    /**
     * Находит проект по данным компании
     * @throws ProjectNotFoundException
     */
    public function findProjectByCompanyData(CompanyData $company): Project
    {
        $project = Project::where('inn', $company->inn)
            ->where(function (Builder $query) use ($company) {
                $query->whereRaw(
                    'LOWER(TRIM(contract_number)) = ?',
                    [StringHelper::normalizeContractNumber($company->contractNumber)]
                );
            })
            ->when($company->additionalNumbers, function (Builder $query) use ($company) {
                $query->whereJsonContains(
                    'additional_contract_number',
                    StringHelper::normalizeAdditionalNumber($company->additionalNumbers)
                );
            })
            ->first();

        if (!$project) {
            throw new ProjectNotFoundException(
                $company->inn,
                $company->contractNumber,
                $company->additionalNumbers
            );
        }

        return $project;
    }
}
