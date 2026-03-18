@php
    use App\Enums\PaymentSource;
@endphp

<div class="tw-p-6">
    <h1>Детализация актов {{ $client->name }}</h1>

    <div class="tw-overflow-x-auto">
        @if (empty($workActs))
        @else
            @foreach ($workActs as $act)
                <div x-data="{ expanded: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            Акт №{{ $act->number }} от {{ $act->creation_date->format('d.m.Y') }}
                        </div>
                        <button class="border-0 bg-transparent font-semibold" @click="expanded = !expanded">
                            <svg :class="expanded ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24">
                                <path d="M480-345 240-585l56-56 184 184 184-184 56 56-240 240Z" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-4" x-show="expanded" x-transition>
                        <table class="w-full text-center text-sm text-gray-500">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                                <tr>
                                    <th class="px-6 py-3">№</th>
                                    <th class="px-6 py-3">Наименование работ, услуг</th>
                                    <th class="px-6 py-3">Кол-во</th>
                                    <th class="px-6 py-3">Ед.</th>
                                    <th class="px-6 py-3">Цена</th>
                                    <th class="px-6 py-3">Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($act['items'] as $item)
                                    <tr class="border-b bg-white">
                                        <td class="px-6 py-4">{{ $item['number'] }}</td>
                                        <td>{{ $item['name'] }}</td>
                                        <td>{{ $item['quantity'] }}</td>
                                        <td>{{ $item['unit'] }}</td>
                                        <td>{{ Number::currency($item['price'], in: 'RUB') }}</td>
                                        <td>{{ Number::currency($item['price'] * $item['quantity'], in: 'RUB') }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>Итого:</td>
                                    <td>{{ Number::currency($act['price'], in: 'RUB') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="tw-flex tw-justify-end tw-mt-4">
        <button class="btn" type="button" wire:click="$dispatch('closeModal')">Закрыть</button>
    </div>
</div>
