<?php

namespace Src\Application\Reports\GetList;

use DateTimeImmutable;
use Src\Domain\ValueObjects\DateTimeRange;

class GetReportsListQueryHandler
{
    private readonly ReportsListDataProviderInterface $dataProvider;

    public function __construct(ReportsListDataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function handle(GetReportsListQuery $query): array
    {
        $period = new DateTimeRange(
            DateTimeImmutable::createFromInterface($query->periodFrom),
            DateTimeImmutable::createFromInterface($query->periodTo)
        );

        return $this->dataProvider->getList($query->showInactiveProjects, $period);
    }
}
