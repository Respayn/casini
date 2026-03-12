<?php

namespace Src\Application\Reports\GetList;

use DateTime;

class GetReportsListQuery
{
    public function __construct(
        public bool $showInactiveProjects = false,
        public DateTime $periodFrom = new DateTime(),
        public DateTime $periodTo = new DateTime(),
        public ?int $userId = null,
    ) {}
}
