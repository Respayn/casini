<?php

namespace Src\Application\Reports\Update;

class UpdateReportCommand
{
    public function __construct(
        public int $reportId,
        public ?bool $isReady = null,
        public ?bool $isAccepted = null,
        public ?bool $isSent = null,
    ) {}
}
