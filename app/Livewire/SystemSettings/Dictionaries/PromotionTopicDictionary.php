<?php

namespace App\Livewire\SystemSettings\Dictionaries;

use App\Livewire\Forms\SystemSettings\Dictionaries\PromotionTopicForm;
use App\Services\PromotionTopicService;
use Illuminate\Support\Collection;
use Livewire\Component;

class PromotionTopicDictionary extends Component
{
    private PromotionTopicService $promotionTopicService;

    public Collection $promotionTopics;

    public PromotionTopicForm $form;

    public int $selectedPromotionTopicId = 0;

    public function boot(PromotionTopicService $promotionTopicService)
    {
        $this->promotionTopicService = $promotionTopicService;
    }

    public function mount()
    {
        $this->promotionTopics = $this->promotionTopicService->getPromotionTopics();
    }

    public function store()
    {
        $this->validate();

        if ($this->selectedPromotionTopicId !== 0) {
            $this->promotionTopicService->updatePromotionTopic($this->form);
        } else {
            $this->promotionTopicService->createPromotionTopic($this->form);
        }

        $this->pull('selectedPromotionTopicId');
        $this->form->reset();
        $this->promotionTopics = $this->promotionTopicService->getPromotionTopics();
        $this->dispatch('modal-hide', name: 'promotion-topic-add-modal');
    }

    public function delete()
    {
        $this->promotionTopicService->deletePromotionTopic($this->pull('selectedPromotionTopicId'));
        $this->promotionTopics = $this->promotionTopicService->getPromotionTopics();
        $this->dispatch('modal-hide', name: 'promotion-topic-delete-modal');
    }

    public function render()
    {
        return view('livewire.system-settings.dictionaries.promotion-topic-dictionary');
    }
}
