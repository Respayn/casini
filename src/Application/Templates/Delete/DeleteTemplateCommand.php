<?php

namespace Src\Application\Templates\Delete;

class DeleteTemplateCommand
{
    public function __construct(public int $templateId) {}
}
