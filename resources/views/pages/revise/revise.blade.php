<div>
	{{-- Шапка компонента --}}
	<div class="flex justify-between">
		<h1 class="mb-7">Сверка по бюджетам</h1>
	</div>

	{{-- Фильтры --}}
	<div class="flex items-center">
		<div class="flex mr-3.5">
			Тут будут фильтры
		</div>
	</div>

	<div class="mt-20 flex flex-col items-center gap-4">
		<div class="tw-h-[75vh] tw-overflow-auto" wire:loading.remove>
			@if ($this->employeesData->isEmpty())
				<div class="tw-text-center tw-italic">Нет данных</div>
			@else
				<table class="table-bordered table">
					<tbody>
						@foreach ($this->employeesData as $employee)
							@if (empty($employee->clients))
								@continue
							@endif

							{{-- Строка с именем сотрудника --}}
							<tr>
								<th class="tw-text-center" colspan="9">{{ $employee->name }}</th>
							</tr>

							{{-- Подзаголовки для сотрудника --}}
							<tr>
								<th>Дата</th>
								<th>
									<div class="tw-flex tw-gap-1 tw-justify-center tw-items-center">
										<x-form.form-label tooltip="Данные подтягиваются из справочника клиентов">Клиент</x-form.form-label>
									</div>
								</th>
								<th>
									<div class="tw-flex tw-gap-1 tw-justify-center tw-items-center">
										<span>Канал</span>
									</div>
								</th>
								<th>
									<div class="tw-flex tw-gap-1 tw-justify-center tw-items-center">
										<x-form.form-label
											tooltip="Поступления подтягиваются из 1С по указанному контрагенту">Поступления</x-form.form-label>
									</div>
								</th>
								<th>
									<div class="tw-flex tw-gap-1 tw-justify-center tw-items-center">
										<x-form.form-label tooltip="Выданные кредиты контрагенту, указываются в ДРС">Кредиты</x-form.form-label>
									</div>
								</th>
								<th>
									<div class="tw-flex tw-gap-1 tw-justify-center tw-items-center">
										<x-form.form-label tooltip="Пополнения кабинета в ДРС">Пополнение кабинета</x-form.form-label>
									</div>
								</th>
								<th>
									<div class="tw-flex tw-gap-1 tw-justify-center tw-items-center">
										<x-form.form-label tooltip="Акты тянутся из 1С">Акты</x-form.form-label>
									</div>
								</th>
								<th>
									<div class="tw-flex tw-gap-1 tw-justify-center tw-items-center">
										<x-form.form-label tooltip="Расход подтягивается из Директа">Расход в Директе</x-form.form-label>
									</div>
								</th>
							</tr>

							{{-- Данные --}}
							@foreach ($employee->clients as $client)
								@foreach ($client->channels as $channel)
									@php
										$rowspan = count($channel->revises) + 1;
									@endphp

									@foreach ($channel->revises as $revise)
										<tr>
											{{-- Дата --}}
											<td>{{ Str::ucfirst($revise->date->locale('ru_RU')->translatedFormat('F Y')) }}</td>
											@if ($loop->index === 0)
												{{-- Клиент --}}
												<td class="!tw-text-center" rowspan="{{ $rowspan }}">{{ $client->name }}</td>

												{{-- Канал --}}
												<td class="!tw-text-center" rowspan="{{ $rowspan }}">{{ $channel->name }}</td>
											@endif

											{{-- Поступления --}}
											<td class="!tw-text-center tw-text-green-500">
												{{ $revise->income === '-' ? '-' : Number::currency($revise->income, in: 'RUB') }}
												<a class="tw-cursor-pointer"
													onclick="Livewire.dispatch(
                                                        'openModal', 
                                                        { 
                                                            component: 'ad-budget-revise.modal.income-details', 
                                                            arguments: { 
                                                                client: {{ $client->id }},
                                                                month: '{{ $revise->date->format('Y-m') }}',
                                                                channelId: {{ $channel->id ?? 'null' }},
                                                                managerId: {{ $employee->id ?? 'null' }}
                                                            }
                                                        }
                                                    )">
													+
												</a>
											</td>
											<td class="!tw-text-center">
												{{ $revise->credit === '-' ? '-' : Number::currency($revise->credit, in: 'RUB') }}
												<a class="tw-cursor-pointer"
													onclick="Livewire.dispatch(
                                                        'openModal',
                                                        {
                                                            component: 'ad-budget-revise.modal.credit-details',
                                                            arguments: {
                                                                client: {{ $client->id }},
                                                                month: '{{ $revise->date->format('Y-m') }}',
                                                                channelId: {{ $channel->id ?? 'null' }},
                                                                managerId: {{ $employee->id ?? 'null' }}
                                                            }
                                                        }
                                                    )">
													+
												</a>
											</td>
											<td class="!tw-text-center tw-text-purple-500">
												{{ $revise->cabinetReplenishment === '-' ? '-' : Number::currency($revise->cabinetReplenishment, in: 'RUB') }}
												<a class="tw-cursor-pointer"
													onclick="Livewire.dispatch(
                                                        'openModal',
                                                        {
                                                            component: 'ad-budget-revise.modal.cabinet-replenishment-details',
                                                            arguments: {
                                                                client: {{ $client->id }},
                                                                month: '{{ $revise->date->format('Y-m') }}',
                                                                channelId: {{ $channel->id ?? 'null' }},
                                                                managerId: {{ $employee->id ?? 'null' }}
                                                            }
                                                        }
                                                    )">+</a>
											</td>
											<td class="!tw-text-center">
												{{ Number::currency($revise->workActsSum, in: 'RUB') }}
												<a class="tw-cursor-pointer"
													wire:click="$dispatch(
                                                    'openModal',
                                                    {
                                                        component: 'ad-budget-revise.modal.work-act-details',
                                                        arguments: {
                                                            client: {{ $client->id }},
                                                            month: '{{ $revise->date->format('Y-m') }}',
                                                            channel: {{ $channel->id ?? 'null' }}
                                                        }
                                                    }
                                                )">+</a>
											</td>
											<td class="!tw-text-center tw-text-purple-500">
												{{ $revise->outcome === '-' ? '-' : Number::currency($revise->outcome, in: 'RUB') }}
											</td>
										</tr>
									@endforeach
									<tr class="tw-font-bold">
										<td class="!tw-text-right">Итого</td>
										<td class="!tw-text-center tw-text-green-500">
											{{ $channel->income === '-' ? '-' : Number::currency($channel->income, in: 'RUB') }}
										</td>
										<td class="!tw-text-center">
											{{ $channel->credit === '-' ? '-' : Number::currency($channel->credit, in: 'RUB') }}
										</td>
										<td class="!tw-text-center tw-text-purple-500">
											@if ($channel->cabinetReplenishment === '-')
												-
											@else
												<span class="tw-text-xs">{{ Number::currency($channel->cabinetReplenishment, in: 'RUB') }}</span>
												<span
													class="tw-text-sm">({{ Number::currency($channel->cabinetReplenishment - $channel->income, in: 'RUB') }})</span>
											@endif
										</td>
										<td class="!tw-text-center">
											<span class="tw-text-xs">{{ Number::currency($channel->workActsSum, in: 'RUB') }}</span>
											<span
												class="tw-text-sm">({{ Number::currency($channel->workActsSum - $channel->income, in: 'RUB') }})</span>
										</td>
										<td class="!tw-text-center tw-text-purple-500">
											@if ($channel->outcome === '-')
												-
											@else
												<span class="tw-text-xs">{{ Number::currency($channel->outcome, in: 'RUB') }}</span>
												<span class="tw-text-sm">({{ Number::currency($channel->outcome - $channel->income, in: 'RUB') }})</span>
											@endif
										</td>
									</tr>
								@endforeach
							@endforeach

							{{-- Итого по сотруднику --}}
							<tr class="tw-bg-green-200">
								<td class="!tw-text-right">Итого по сотруднику</td>
								<td colspan="2"></td>
								<td class="!tw-text-center tw-font-bold">
									{{ $employee->income === 0 ? 0 : Number::currency($employee->income, in: 'RUB') }}
								</td>
								<td class="!tw-text-center tw-font-bold">
									{{ $employee->credit === 0 ? 0 : Number::currency($employee->credit, in: 'RUB') }}
								</td>
								<td class="!tw-text-center tw-font-bold">
									{{ $employee->cabinetReplenishment === 0 ? 0 : Number::currency($employee->cabinetReplenishment, in: 'RUB') }}
								</td>
								<td class="!tw-text-center tw-font-bold">
									{{ $employee->workActsSum === 0 ? 0 : Number::currency($employee->workActsSum, in: 'RUB') }}
								</td>
								<td class="!tw-text-center tw-font-bold">
									{{ $employee->outcome === '-' ? '-' : Number::currency($employee->outcome, in: 'RUB') }}
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</div>
	</div>
</div>
