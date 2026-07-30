<?php

return [
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_if' => 'Поле :attribute обязательно для заполнения.',
    'required_with' => 'Поле :attribute обязательно для заполнения.',
    'boolean' => 'Поле :attribute должно содержать true или false.',
    'string' => 'Поле :attribute должно быть строкой.',
    'numeric' => 'Поле :attribute должно быть числом.',
    'integer' => 'Поле :attribute должно быть целым числом.',
    'in' => 'Выбранное значение для :attribute неверно.',
    'max' => [
        'string' => 'Поле :attribute не должно превышать :max символов.',
        'numeric' => 'Поле :attribute не должно быть больше :max.',
    ],
    'min' => [
        'numeric' => 'Поле :attribute не должно быть меньше :min.',
    ],
    'gte' => [
        'numeric' => 'Поле :attribute должно быть не меньше :value.',
    ],
    'url' => 'Поле :attribute должно быть корректным URL.',
    'exists' => 'Выбранное значение для :attribute неверно.',
    'array' => 'Поле :attribute должно быть массивом.',
];
