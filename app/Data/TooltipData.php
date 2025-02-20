<?php

namespace App\Data;

use Livewire\Wireable;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

class TooltipData extends Data implements Wireable
{
    use WireableData;

    public int $id;
    public string $code;
    public string $path;
    public string $label;
    public string $content;
}
