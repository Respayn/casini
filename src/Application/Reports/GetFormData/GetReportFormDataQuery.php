<?php

namespace Src\Application\Reports\GetFormData;

class GetReportFormDataQuery
{
    public function __construct(
        public readonly ?int $userId = null,
    ) {}
}
