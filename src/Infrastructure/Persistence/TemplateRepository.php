<?php

namespace Src\Infrastructure\Persistence;

use App\Models\Template as EloquentTemplate;
use DateTimeImmutable;
use Illuminate\Support\Facades\Storage;
use Src\Domain\Templates\Template;
use Src\Domain\Templates\TemplateRepositoryInterface;

class TemplateRepository implements TemplateRepositoryInterface
{
    public function findAll(): array
    {
        return EloquentTemplate::all()
            ->map(fn ($t) => $this->mapToEntity($t))
            ->toArray();
    }

    public function findById(int $id): Template
    {
        $template = EloquentTemplate::query()->findOrFail($id);

        return $this->mapToEntity($template);
    }

    public function save(Template $template): void
    {
        if ($template->isPersisted()) {
            $eloquentTemplate = EloquentTemplate::query()->findOrFail($template->getId());
        } else {
            $eloquentTemplate = new EloquentTemplate;
            $eloquentTemplate->created_at = $template->getCreatedAt();
            $eloquentTemplate->updated_at = $template->getCreatedAt();
        }

        $eloquentTemplate->name = $template->getName();
        $eloquentTemplate->path = $template->getPath();
        $eloquentTemplate->save();
    }

    public function remove(Template $template): void
    {
        EloquentTemplate::destroy($template->getId());
        Storage::delete($template->getPath());
    }

    private function mapToEntity(EloquentTemplate $eloquentTemplate): Template
    {
        return Template::restore(
            $eloquentTemplate->id,
            $eloquentTemplate->name,
            $eloquentTemplate->path,
            DateTimeImmutable::createFromInterface($eloquentTemplate->created_at)
        );
    }
}
