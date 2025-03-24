<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use App\Data\ProjectUtmMappingData;
use App\Enums\UtmType;
use App\Models\ProjectUtmMapping;
use Illuminate\Validation\Rules\Enum;
use Livewire\Form;

class ProjectUtmMappingForm extends Form
{
    /**
     * Массив UTM мэппингов.
     *
     * @var array
     */
    public array $utmMappings = [];

    public function rules()
    {
        return [
            'utmMappings' => 'nullable|array',
            'utmMappings.*.id' => 'nullable|integer',
            'utmMappings.*.utmType' => ['required_with:utmMappings', new Enum(UtmType::class)],
            'utmMappings.*.utmValue' => ['required_with:utmMappings', 'string', 'max:255'],
            'utmMappings.*.replacementValue' => 'required_with:utmMappings|string|max:255',
        ];
    }

    /**
     * Метод для заполнения данных формы из массива DTO или моделей UTM мэппинга.
     *
     * @param array<ProjectUtmMappingData|ProjectUtmMapping> $utmMappings
     * @return void
     */
    public function from(array $utmMappings)
    {
        $this->utmMappings = [];

        foreach ($utmMappings as $utmMapping) {
            $this->utmMappings[] = [
                'id' => $utmMapping['id'],
                'utmType' => UtmType::from($utmMapping['utm_type']),
                'utmValue' => $utmMapping['utm_value'],
                'replacementValue' => $utmMapping['replacement_value'],
            ];
        }
    }

    /**
     * Метод для добавления нового мэппинга.
     *
     * @return void
     */
    public function addMapping()
    {
        $this->utmMappings[] = [
            'id' => null,
            'utmType' => UtmType::UTM_CAMPAIGN,
            'utmValue' => '',
            'replacementValue' => '',
        ];
    }

    /**
     * Метод для удаления мэппинга по индексу.
     *
     * @param int $index
     * @return void
     */
    public function removeMapping($index)
    {
        unset($this->utmMappings[$index]);
        $this->utmMappings = array_values($this->utmMappings); // Сброс индексов массива
    }
}
