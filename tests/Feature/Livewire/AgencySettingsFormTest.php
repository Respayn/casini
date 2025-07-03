<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Tests\TestCase;

class AgencySettingsFormTest extends TestCase
{

    public function test_renders_successfully()
    {
        Livewire::test(\App\Livewire\SystemSettings\Agency\AgencySettingsComponent::class)
            ->assertStatus(200);
    }
}
