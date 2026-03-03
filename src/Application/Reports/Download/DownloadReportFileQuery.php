<?php

namespace Src\Application\Reports\Download;

class DownloadReportFileQuery
{
    public function __construct(
        public int $reportId
    ) {}
}
