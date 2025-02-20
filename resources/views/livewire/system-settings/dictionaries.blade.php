<div>
    <h1 class="text-primary-text pb-[30px] text-xl font-semibold">Справочники</h1>

    <x-panel.accordion class="max-w-1/2">
        <x-panel.accordion-panel>
            <x-panel.accordion-header>
                <div>Справочник моделей атрибуции</div>
                <div class="w-3/4 text-sm italic font-normal text-gray-400">
                    Модель атрибуции влияет на то, как источнику трафика присваиваются визит и конверсии в Яндекс
                    Метрике
                </div>
            </x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table>
                    <x-data.table-rows>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Автоматическая
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Последний значимый переход
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Полный переход
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Последний переход
                            </x-data.table-cell>
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
                <div class="w-3/4 text-sm italic font-normal text-gray-400">
                    Изменение моделей атрибуции через программиста
                </div>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>
                <div>Справочник ставок</div>
                <div class="w-3/4 text-sm italic font-normal text-gray-400">
                    После добавления новой роли - настройте права в разделе “Продукты и права”
                </div>
            </x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table>
                    <x-data.table-columns>
                        <x-data.table-column>Название</x-data.table-column>
                        <x-data.table-column>Ставка (₽ / час)</x-data.table-column>
                        <x-data.table-column>Применение ставки с:</x-data.table-column>
                        <x-data.table-column>До (включительно):</x-data.table-column>
                        <x-data.table-column>Действия</x-data.table-column>
                        <x-data.table-column>Удаление</x-data.table-column>
                    </x-data.table-columns>

                    <x-data.table-rows>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Менеджер
                            </x-data.table-cell>
                            <x-data.table-cell>
                                2 358 ₽
                            </x-data.table-cell>
                            <x-data.table-cell>
                                01.01.2023
                            </x-data.table-cell>
                            <x-data.table-cell>
                                -
                            </x-data.table-cell>
                            <x-data.table-cell>

                            </x-data.table-cell>
                            <x-data.table-cell>

                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell rowspan="2">
                                SEO-специалист
                            </x-data.table-cell>
                            <x-data.table-cell>
                                2 400 ₽
                            </x-data.table-cell>
                            <x-data.table-cell>
                                05.04.2024
                            </x-data.table-cell>
                            <x-data.table-cell>
                                -
                            </x-data.table-cell>
                            <x-data.table-cell rowspan="2">

                            </x-data.table-cell>
                            <x-data.table-cell rowspan="2">

                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                2 135 ₽
                            </x-data.table-cell>
                            <x-data.table-cell>
                                01.01.2023
                            </x-data.table-cell>
                            <x-data.table-cell>
                                04.04.2024
                            </x-data.table-cell>
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник KPI</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table class="min-w-52">
                    <x-data.table-rows>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Трафик
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Лиды
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Позиции
                            </x-data.table-cell>
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
                <div class="w-3/4 text-sm italic font-normal text-gray-400">
                    Изменение KPI через программиста
                </div>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник подсказок пользователю</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table>
                    <x-data.table-columns>
                        <x-data.table-column>Название</x-data.table-column>
                        <x-data.table-column>Ставка (₽ / час)</x-data.table-column>
                        <x-data.table-column>Применение ставки с:</x-data.table-column>
                        <x-data.table-column>До (включительно):</x-data.table-column>
                        <x-data.table-column>Действия</x-data.table-column>
                        <x-data.table-column>Удаление</x-data.table-column>
                    </x-data.table-columns>

                    <x-data.table-rows>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Менеджер
                            </x-data.table-cell>
                            <x-data.table-cell>
                                2 358 ₽
                            </x-data.table-cell>
                            <x-data.table-cell>
                                01.01.2023
                            </x-data.table-cell>
                            <x-data.table-cell>
                                -
                            </x-data.table-cell>
                            <x-data.table-cell>

                            </x-data.table-cell>
                            <x-data.table-cell>

                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell rowspan="2">
                                SEO-специалист
                            </x-data.table-cell>
                            <x-data.table-cell>
                                2 400 ₽
                            </x-data.table-cell>
                            <x-data.table-cell>
                                05.04.2024
                            </x-data.table-cell>
                            <x-data.table-cell>
                                -
                            </x-data.table-cell>
                            <x-data.table-cell rowspan="2">

                            </x-data.table-cell>
                            <x-data.table-cell rowspan="2">

                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                2 135 ₽
                            </x-data.table-cell>
                            <x-data.table-cell>
                                01.01.2023
                            </x-data.table-cell>
                            <x-data.table-cell>
                                04.04.2024
                            </x-data.table-cell>
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Портфель продуктов</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник уведомлений продуктов</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник интеграций</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник регионов</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник тематик продвижения</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник переменных в отчетах</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник типов клиенто-проектов</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник регионов Yandex Search API</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник типов контрагентов</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник параметров</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник рекламных систем (ДРС)</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник часовых поясов</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel>
    </x-panel.accordion>
</div>
