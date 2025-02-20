<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class PromotionTopicData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public string $category;
    public string $topic;
}
