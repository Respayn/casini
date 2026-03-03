<?php

namespace Src\Application\Reports\Download;

class DownloadReportFileDto
{
    public function __construct(
        public string $path,
        public string $name
    ) {}
}
