<?php

namespace App\Services;

use App\Livewire\Forms\SystemSettings\Dictionaries\PromotionTopicForm;
use App\Repositories\PromotionTopicRepository;

class PromotionTopicService
{
    private PromotionTopicRepository $repository;

    public function __construct(PromotionTopicRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getPromotionTopics()
    {
        return collect($this->repository->all());
    }

    public function createPromotionTopic(PromotionTopicForm $form)
    {
        $attributes = $form->all();
        $this->repository->save($attributes);
    }

    public function deletePromotionTopic(int $id)
    {
        $this->repository->delete($id);
    }

    public function updatePromotionTopic(PromotionTopicForm $form)
    {
        $attributes = $form->all();
        $this->repository->save($attributes);
    }
}
