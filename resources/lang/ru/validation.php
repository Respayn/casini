<?php

return [
    'required' => 'Поле :attribute обязательно для заполнения.',
    'boolean' => 'Поле :attribute должно содержать true или false.',
    'string' => 'Поле :attribute должно быть строкой.',
    'max' => [
        'string' => 'Поле :attribute не должно превышать :max символов.',
        'file' => 'Размер файла :attribute не должен превышать :max Кб.',
    ],
    'url' => 'Поле :attribute должно быть корректным URL.',
    'exists' => 'Выбранное значение для :attribute неверно.',
    'array' => 'Поле :attribute должно быть массивом.',
    'image' => 'Поле :attribute должно быть изображением.',
    'mimes' => 'Поле :attribute должно быть файлом одного из типов: :values.',
    'uploaded' => 'Не удалось загрузить файл :attribute. Попробуйте ещё раз или выберите другой файл.',
    'attributes' => [
        'form.photo' => 'фото пользователя',
        'photo' => 'фото пользователя',
    ],
];
