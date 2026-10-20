<?php

namespace Src\Domain\Templates;

final readonly class ReportTag
{
    public function __construct(
        public string $key,
        public string $label,
        public string $category,
        public ReportTagKind $kind,
        public bool $popular = false,
    ) {}

    public function placeholder(): string
    {
        return '{{'.$this->key.'}}';
    }

    public function parameterLabel(): string
    {
        return $this->kind->parameterLabel();
    }

    public function source(): TemplateTagSource
    {
        return TemplateTagSource::fromTag($this->key);
    }
}
