<div class="tw-p-6">
    <h1>Детализация кредитов {{ $client->name }}</h1>

    <div class="tw-overflow-x-auto">
        <table class="tw-border-solid tw-border-0 tw-min-w-full tw-divide-y tw-divide-gray-200">
            <thead>
                <tr>
                    <th></th>
                    <th class="tw-px-4 tw-py-2 tw-text-gray-500 tw-whitespace-nowrap tw-text-center">№</th>
                    <th class="tw-px-4 tw-py-2 tw-text-gray-500 tw-whitespace-nowrap tw-text-center">Дата</th>
                    <th class="tw-px-4 tw-py-2 tw-text-gray-500 tw-whitespace-nowrap tw-text-center">Сумма кредита</th>
                    <th class="tw-px-4 tw-py-2 tw-text-gray-500 tw-whitespace-nowrap tw-text-center">Клиенто-проект</th>
                    <th class="tw-px-4 tw-py-2 tw-text-gray-500 tw-whitespace-nowrap tw-text-center">Комментарий к платежу</th>
                </tr>
            </thead>
            <tbody>
                @if ($credits->isEmpty())
                    <tr>
                        <td colspan="6" class="tw-px-4 tw-py-3 tw-text-center tw-italic">Кредитов нет</td>
                    </tr>
                @else
                    @php
                        $i = 0;
                    @endphp
                    @foreach ($credits as $credit)
                        @foreach ($credit->operations as $operation)
                            <tr @class([
                                'tw-border-solid tw-border-gray-200 tw-border-0 tw-border-t' => $i > 0,
                            ])>
                                <td>
                                    <x-icon-hand-holding-coin class="tw-w-4 tw-h-4 tw-text-gray-500" />
                                </td>
                                <td class="tw-px-4 tw-py-3 tw-text-center">{{ $credit->number }}</td>
                                <td class="tw-px-4 tw-py-3 tw-text-center">{{ $credit->received_date->format('d.m.Y') }}</td>
                                <td class="tw-px-4 tw-py-3 tw-text-center">{{ Number::currency($operation->credit_amount, in: 'RUB') }}</td>
                                <td class="tw-px-4 tw-py-3 tw-text-center">{{ $operation->project?->name ?? '-' }}</td>
                                <td class="tw-px-4 tw-py-3">{{ $operation->payment_description }}</td>
                            </tr>
                            @php
                                $i++;
                            @endphp
                        @endforeach
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="tw-flex tw-justify-end tw-mt-4">
        <button class="btn" type="button" wire:click="$dispatch('closeModal')">Закрыть</button>
    </div>
</div>
