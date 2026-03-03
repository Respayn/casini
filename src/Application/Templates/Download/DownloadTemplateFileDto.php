<?php

namespace Src\Application\Templates\Download;

class DownloadTemplateFileDto
{
    public function __construct(
        public string $path,
        public string $name
    ) {}
}
