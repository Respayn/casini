<?php

namespace Src\Domain\Templates;

interface TemplateRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): Template;
    public function save(Template $template): void;
    public function remove(Template $template): void;
}