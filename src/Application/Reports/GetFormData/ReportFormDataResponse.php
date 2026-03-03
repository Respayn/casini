<?php

namespace Src\Application\Reports\GetFormData;

class ReportFormDataResponse
{
    public function __construct(
        public array $projects,
        public array $formats,
        public array $templates
    ) {}
}
