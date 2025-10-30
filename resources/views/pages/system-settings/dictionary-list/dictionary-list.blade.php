<div>
    <h1 class="pb-[30px]">Справочники</h1>

    <x-panel.accordion class="max-w-1/2">
        <x-panel.accordion-panel>
            <x-panel.accordion-header>
                <div>Справочник моделей атрибуции</div>
                <div class="w-3/4 text-sm font-normal italic text-gray-400">
                    Модель атрибуции влияет на то, как источнику трафика присваиваются визит и конверсии в Яндекс
                    Метрике
                </div>
            </x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.attribute-model-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>
                <div>Справочник ставок</div>
                <div class="w-3/4 text-sm font-normal italic text-gray-400">
                    После добавления новой роли - настройте права в разделе “Продукты и права”
                </div>
            </x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.rate-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник KPI</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table class="min-w-52">
                    <x-data.table-rows>
                        @foreach (App\Enums\Kpi::cases() as $kpi)
                            <x-data.table-row>
                                <x-data.table-cell>
                                    {{ $kpi->label() }}
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
                <div class="w-3/4 text-sm font-normal italic text-gray-400">
                    Изменение KPI через программиста
                </div>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник подсказок пользователю</x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.tooltip-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Портфель продуктов</x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.product-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник уведомлений продуктов</x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.product-notification-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник интеграций</x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.integration-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник регионов</x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.promotion-region-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник тематик продвижения</x-panel.accordion-header>
            <x-panel.accordion-content>
                <livewire:system-settings.dictionaries.promotion-topic-dictionary />
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        {{-- Отложено до момента реализации функционала отчетов --}}
        {{-- <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник переменных в отчетах</x-panel.accordion-header>
            <x-panel.accordion-content>

            </x-panel.accordion-content>
        </x-panel.accordion-panel> --}}

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник типов клиенто-проектов</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table class="min-w-52">
                    <x-data.table-rows>
                        @foreach (App\Enums\ProjectType::cases() as $projectType)
                            <x-data.table-row>
                                <x-data.table-cell>
                                    {{ $projectType->label() }}
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
                <div class="w-3/4 text-sm font-normal italic text-gray-400">
                    Добавление новых типов каналов через программиста
                </div>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник регионов Yandex Search API</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table class="min-w-52">
                    <x-data.table-columns>
                        <x-data.table-column>Идентификатор</x-data.table-column>
                        <x-data.table-column>Регион</x-data.table-column>
                    </x-data.table-columns>
                    <x-data.table-rows>
                        @foreach (App\Enums\SearchRegion::cases() as $searchRegion)
                            <x-data.table-row>
                                <x-data.table-cell>
                                    {{ $searchRegion }}
                                </x-data.table-cell>
                                <x-data.table-cell>
                                    {{ $searchRegion->label() }}
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
                <div class="w-3/4 text-sm font-normal italic text-gray-400">
                    Добавление новых типов каналов через программиста
                </div>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник типов контрагентов</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table class="min-w-52">
                    <x-data.table-rows>
                        @foreach (App\Enums\LegalForm::cases() as $legalForm)
                            <x-data.table-row>
                                <x-data.table-cell>
                                    {{ $legalForm->label() }}
                                </x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
                <div class="w-3/4 text-sm font-normal italic text-gray-400">
                    Добавление новых типов через программиста
                </div>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        {{-- Справочник статичный, пока неясно, нужно ли выносить параметры в БД или Enum --}}
        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник параметров</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table>
                    <x-data.table-columns>
                        <x-data.table-column>KPI</x-data.table-column>
                        <x-data.table-column>Тип клиенто-проекта</x-data.table-column>
                        <x-data.table-column>Параметр</x-data.table-column>
                    </x-data.table-columns>

                    <x-data.table-rows>
                        <x-data.table-row>
                            <x-data.table-cell rowspan="3">
                                Трафик
                            </x-data.table-cell>
                            <x-data.table-cell rowspan="3">
                                Контекст
                            </x-data.table-cell>
                            <x-data.table-cell>
                                CPC
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Рекламный бюджет
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Визитов
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell rowspan="3">
                                Лиды
                            </x-data.table-cell>
                            <x-data.table-cell rowspan="3">
                                Контекст
                            </x-data.table-cell>
                            <x-data.table-cell>
                                CPL
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Рекламный бюджет
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Лидов
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell rowspan="2">
                                Трафик
                            </x-data.table-cell>
                            <x-data.table-cell rowspan="2">
                                SEO
                            </x-data.table-cell>
                            <x-data.table-cell>
                                Объем визитов
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Конверсии
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell rowspan="2">
                                Трафик
                            </x-data.table-cell>
                            <x-data.table-cell rowspan="2">
                                Позиции
                            </x-data.table-cell>
                            <x-data.table-cell>
                                % позиций в ТОП 10
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Конверсии
                            </x-data.table-cell>
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        {{-- Пока справочник статичный. В дальнейшем нужно будет вынести в БД --}}
        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник рекламных систем (ДРС)</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table>
                    <x-data.table-columns>
                        <x-data.table-column>Рекламная система</x-data.table-column>
                    </x-data.table-columns>

                    <x-data.table-rows>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Яндекс
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Google
                            </x-data.table-cell>
                        </x-data.table-row>
                        <x-data.table-row>
                            <x-data.table-cell>
                                Вконтакте
                            </x-data.table-cell>
                        </x-data.table-row>
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>

        {{-- Справочник статичный, пока неясно, нужно ли выносить пояса в БД --}}
        <x-panel.accordion-panel>
            <x-panel.accordion-header>Справочник часовых поясов</x-panel.accordion-header>
            <x-panel.accordion-content>
                <x-data.table>
                    <x-data.table-rows>
                        @foreach(\App\Dictionaries\TimeZoneDictionary::list() as $tz)
                            <x-data.table-row>
                                <x-data.table-cell>{{ $tz['label'] }}</x-data.table-cell>
                            </x-data.table-row>
                        @endforeach
                    </x-data.table-rows>
                </x-data.table>
            </x-panel.accordion-content>
        </x-panel.accordion-panel>
    </x-panel.accordion>
</div>