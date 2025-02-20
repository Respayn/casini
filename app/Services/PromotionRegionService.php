<?php

namespace App\Services;

use App\Data\PromotionRegionData;
use App\Livewire\Forms\SystemSettings\Dictionaries\CreatePromotionRegionForm;
use App\Repositories\PromotionRegionRepository;

class PromotionRegionService
{
    private PromotionRegionRepository $repository;

    /**
     * Create a new class instance.
     */
    public function __construct(PromotionRegionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getPromotionRegions()
    {
        return collect($this->repository->all());
    }

    public function createPromotionRegion(CreatePromotionRegionForm $form)
    {
        $attributes = $form->all();
        $this->repository->save($attributes);
    }

    public function deletePromotionRegion(int $id)
    {
        $this->repository->delete($id);
    }

    public function updatePromotionRegion(PromotionRegionData $region)
    {
        $this->repository->save($region->toArray());
    }
}
