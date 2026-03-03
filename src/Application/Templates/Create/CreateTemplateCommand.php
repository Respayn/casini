<?php

namespace Src\Application\Templates\Create;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateTemplateCommand
{
    public function __construct(
        public TemporaryUploadedFile $file
    ) {}
}
