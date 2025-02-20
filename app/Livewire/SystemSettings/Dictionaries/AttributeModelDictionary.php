<?php

namespace App\Livewire\SystemSettings\Dictionaries;

use App\Enums\AttributionModel;
use Livewire\Component;

class AttributeModelDictionary extends Component
{
    public function render()
    {
        return view('livewire.system-settings.dictionaries.attribute-model-dictionary')->with([
            'attributionModels' => AttributionModel::cases()
        ]);
    }
}
