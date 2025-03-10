<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TooltipSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tooltips')->insert([
            [
                'code' => 'settings_products_delete',
                'path' => 'Настройки > Продукты и права',
                'label' => 'Удаление',
                'content' => 'Полные права у пользователя на работу с продуктом'
            ],
            [
                'code' => 'settings_users_rate',
                'path' => 'Настройки > Пользователи и роли',
                'label' => 'Ставка (₽ / час)',
                'content' => 'Ставка зависит от роли пользователя и настраивается в справочнике ставок'
            ],
            [
                'code' => 'settings_users_add_status',
                'path' => 'Настройки > Пользователи и роли > Добавить пользователя',
                'label' => 'Статус',
                'content' => 'При выборе статуса “Неактивен” блокируется вход в систему'
            ],
            [
                'code' => 'settings_users_add_role',
                'path' => 'Настройки > Пользователи и роли > Добавить пользователя',
                'label' => 'Роль',
                'content' => 'От выбранной роли зависят права в системе'
            ],
            [
                'code' => 'settings_users_add_contacts',
                'path' => 'Настройки > Пользователи и роли > Добавить пользователя',
                'label' => 'Контактная информация',
                'content' => 'Отображается в отчетах'
            ],
            [
                'code' => 'settings_users_add_photo',
                'path' => 'Настройки > Пользователи и роли > Добавить пользователя',
                'label' => 'Фото',
                'content' => "- Можно загружать файлы только форматов: jpg, jpeg, png и gif\n- Максимальный вес не более 1 мб."
            ],
            [
                'code' => 'settings_users_edit_role_rate_enddate',
                'path' => 'Настройки > Пользователи и роли > Редактирование роли и ставки',
                'label' => 'Дата окончания ставки',
                'content' => 'Если хотите запланировать изменение ставки - заполните поле'
            ],
            [
                'code' => 'settings_clients_client',
                'path' => 'Настройки > Клиенты и клиенто-проекты; Настройки > Клиенты и клиенто-проекты > Создать клиента',
                'label' => 'Клиент',
                'content' => 'Заполните название клиента, так клиент будет отображаться во всех продуктах'
            ],
            [
                'code' => 'settings_clients_inn',
                'path' => 'Настройки > Клиенты и клиенто-проекты; Настройки > Клиенты и клиенто-проекты > Создать клиента',
                'label' => 'ИНН',
                'content' => 'Заполните ИНН, так мы сможем автоматически определять операции по клиенту в отчетах'
            ],
            [
                'code' => 'settings_clients_manager',
                'path' => 'Настройки > Клиенты и клиенто-проекты > Создать клиента',
                'label' => 'Менеджер',
                'content' => 'Укажите менеджера, и все будущие клиенто-проекты будут привязаны к данному менеджеру'
            ],
            [
                'code' => 'settings_clients_initial_balance',
                'path' => 'Настройки > Клиенты и клиенто-проекты; Настройки > Клиенты и клиенто-проекты > Создать клиента',
                'label' => 'Начальная статистика взаиморасчетов',
                'content' => 'Заполнение поле учитывается при формировании сверки бюджетов. Значение может быть как положительным (нам должны) так и отрицательным (мы должны). Заполнять поле может только руководитель'
            ]
        ]);
    }
}
