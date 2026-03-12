<?php

namespace Src\Infrastructure\Persistence;

use App\Models\Template as EloquentTemplate;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Src\Domain\Templates\Template;
use Src\Domain\Templates\TemplateInUseException;
use Src\Domain\Templates\TemplateRepositoryInterface;

class TemplateRepository implements TemplateRepositoryInterface
{
    public function findAll(): array
    {
        return EloquentTemplate::all()
            ->map(fn($t) => $this->mapToEntity($t))
            ->toArray();
    }

    public function findById(int $id): Template
    {
        $template = EloquentTemplate::find($id);
        return $this->mapToEntity($template);
    }

    public function save(Template $template): void
    {
        $eloquentTemplate = new EloquentTemplate();
        $eloquentTemplate->name = $template->getName();
        $eloquentTemplate->path = $template->getPath();
        $eloquentTemplate->created_at = $template->getCreatedAt();
        $eloquentTemplate->updated_at = $template->getCreatedAt();
        $eloquentTemplate->save();
    }

    public function remove(Template $template): void
    {
        try {
            EloquentTemplate::destroy($template->getId());
            Storage::delete($template->getPath());
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new TemplateInUseException();
            }

            throw $e;
        }
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
