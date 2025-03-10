<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use App\Models\ProjectBonusCondition;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectBonusGuaranteeForm extends Form
{
    #[Validate('boolean')]
    public bool $bonuses_enabled = false;

    #[Validate('boolean')]
    public bool $calculate_in_percentage = false;

    #[Validate('nullable|numeric|min:0')]
    public ?float $client_payment = null;

    #[Validate('required_if:bonuses_enabled,true|integer|in:1,2,3')]
    public int $start_month = 1;

    public array $intervals = [
        [
            'from_percentage' => '',
            'to_percentage' => '',
            'bonus_amount' => '',
            'bonus_percentage' => '',
        ],
    ];

    public function rules()
    {
        $rules = [
            'bonuses_enabled' => 'boolean',
            'calculate_in_percentage' => 'boolean',
            'client_payment' => 'nullable|numeric|min:0',
            'start_month' => 'required_if:bonuses_enabled,true|integer|in:1,2,3',
            'intervals' => 'required_if:bonuses_enabled,true|array|min:1',
            'intervals.*.from_percentage' => 'required|numeric|min:0|max:100',
            'intervals.*.to_percentage' => 'required|numeric|min:0|max:100|gte:intervals.*.from_percentage',
        ];

        if (!$this->calculate_in_percentage) {
            $rules['intervals.*.bonus_amount'] = 'required|numeric';
        } else {
            $rules['intervals.*.bonus_percentage'] = 'required|numeric|min:-100|max:100';
        }

        return $rules;
    }

    /**
     * Метод для заполнения данных формы из модели бонусных условий.
     *
     * @param ProjectBonusCondition $bonusCondition
     * @return void
     */
    public function fillFromModel(ProjectBonusCondition $bonusCondition)
    {
        $this->bonuses_enabled = $bonusCondition->bonuses_enabled;
        $this->calculate_in_percentage = $bonusCondition->calculate_in_percentage;
        $this->client_payment = $bonusCondition->client_payment;
        $this->start_month = $bonusCondition->start_month;
        $this->intervals = $bonusCondition->intervals()->get()->toArray() ?: [
            [
                'from_percentage' => '',
                'to_percentage' => '',
                'bonus_amount' => '',
                'bonus_percentage' => '',
            ],
        ];
    }

    public function prefixedRules($prefix)
    {
        $rules = $this->rules();

        $prefixedRules = [];
        foreach ($rules as $key => $rule) {
            $prefixedRules[$prefix . $key] = $rule;
        }

        return $prefixedRules;
    }
}
