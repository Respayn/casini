<?php

namespace App\Services;

use App\Repositories\TooltipRepository;
use Illuminate\Support\Collection;

class TooltipService
{
    private TooltipRepository $repository;

    /**
     * Create a new class instance.
     */
    public function __construct(TooltipRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getTooltips(): Collection
    {
        return collect($this->repository->all());
    }

    public function updateTooltip(string $code, string $content)
    {
        $tooltip = $this->repository->findByCode($code);
        $tooltip->content = $content;
        $this->repository->save($tooltip->toArray());
    }
}
