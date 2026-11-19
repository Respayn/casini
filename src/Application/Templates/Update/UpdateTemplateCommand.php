<?php

namespace Src\Application\Templates\Update;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UpdateTemplateCommand
{
    public function __construct(
        public int $templateId,
        public string $name,
        public ?TemporaryUploadedFile $file = null,
    ) {}
}
