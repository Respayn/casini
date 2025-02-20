<?php

namespace App\Livewire\SystemSettings\Dictionaries;

use App\Livewire\Forms\SystemSettings\Dictionaries\CreatePromotionRegionForm;
use App\Services\PromotionRegionService;
use Illuminate\Support\Collection;
use Livewire\Component;

class PromotionRegionDictionary extends Component
{
    private PromotionRegionService $promotionRegionService;

    public Collection $promotionRegions;

    public CreatePromotionRegionForm $createForm;

    public int $selectedPromotionRegionId = 0;

    public function boot(PromotionRegionService $promotionRegionService)
    {
        $this->promotionRegionService = $promotionRegionService;
    }

    public function mount()
    {
        $this->promotionRegions = $this->promotionRegionService->getPromotionRegions();
    }

    public function store()
    {
        $this->validate();
        $this->promotionRegionService->createPromotionRegion($this->createForm);
        $this->createForm->reset();
        $this->promotionRegions = $this->promotionRegionService->getPromotionRegions();
        $this->dispatch('modal-hide', name: 'promotion-region-add-modal');
    }

    public function delete()
    {
        $this->promotionRegionService->deletePromotionRegion($this->pull('selectedPromotionRegionId'));
        $this->promotionRegions = $this->promotionRegionService->getPromotionRegions();
        $this->dispatch('modal-hide', name: 'promotion-region-delete-modal');
    }

    public function update(int $id)
    {
        $promotionRegion = $this->promotionRegions->firstWhere('id', $id);
        $this->promotionRegionService->updatePromotionRegion($promotionRegion);
    }

    public function render()
    {
        return view('livewire.system-settings.dictionaries.promotion-region-dictionary');
    }
}
