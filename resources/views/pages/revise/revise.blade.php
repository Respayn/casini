<div>
	{{-- Шапка компонента --}}
	<div class="flex justify-between">
		<h1 class="mb-7">Сверка по бюджетам</h1>
	</div>

	{{-- Фильтры --}}
	<div class="flex items-center">
		<div class="flex gap-2">
			<x-form.month-picker wire:model.live="dateFrom" />
			<x-form.month-picker wire:model.live="dateTo" />
		</div>

		<div class="ml-2.5">
			<label>Показать расходы в директе:</label>
			<x-form.checkbox wire:model.live="fetchFromDirect" />
		</div>

		<div class="ml-2.5">
			<label>Клиент</label>
			<x-form.select wire:model.live="clientId" :options="$this->clients" />
		</div>

		<div class="ml-2.5">
			<label>Менеджер</label>
			<x-form.select wire:model.live="managerId" :options="$this->managers" />
		</div>

		<div class="ml-2.5">
			<label>Канал</label>
			<x-form.select wire:model.live="channelId" :options="$this->channels" />
		</div>
	</div>

	<div class="mt-20 flex flex-col items-center gap-4">
		<div class="tw-h-[75vh] tw-overflow-auto" wire:loading.remove>
			@if ($this->employeesData->isEmpty())
				<div class="tw-text-center tw-italic">Нет данных</div>
			@else
				<x-panel.scroll-panel style="max-height: calc(100vh - 300px); padding-bottom: 16px">
					<x-data.table>
						<tbody>
							@foreach ($this->employeesData as $employee)
								@if (empty($employee->clients))
									@continue
								@endif

								{{-- Строка с именем сотрудника --}}

								<tr>
									<x-data.table-column class="tw-text-center" colspan="8">
										<span>{{ $employee->name }}</span>
									</x-data.table-column>
								</tr>

								<x-data.table-columns>
									<x-data.table-column class="whitespace-nowrap">
										<span>Дата</span>
									</x-data.table-column>

									<x-data.table-column class="whitespace-nowrap">
										<span>Клиент</span>
										<x-overlay.tooltip>
											Данные подтягиваются из справочника клиентов
										</x-overlay.tooltip>
									</x-data.table-column>

									<x-data.table-column class="whitespace-nowrap">
										<span>Канал</span>
									</x-data.table-column>

									<x-data.table-column class="whitespace-nowrap">
										<span>Поступления</span>
										<x-overlay.tooltip>
											Поступления подтягиваются из 1С по указанному контрагенту
										</x-overlay.tooltip>
									</x-data.table-column>

									<x-data.table-column class="whitespace-nowrap">
										<span>Кредиты</span>
										<x-overlay.tooltip>
											Выданные кредиты контрагенту, указываются в ДРС
										</x-overlay.tooltip>
									</x-data.table-column>

									<x-data.table-column class="whitespace-nowrap">
										<span>Пополнение кабинета</span>
										<x-overlay.tooltip>
											Пополнения кабинета в ДРС
										</x-overlay.tooltip>
									</x-data.table-column>

									<x-data.table-column class="whitespace-nowrap">
										<span>Акты</span>
										<x-overlay.tooltip>
											Акты тянутся из 1С
										</x-overlay.tooltip>
									</x-data.table-column>

									<x-data.table-column class="whitespace-nowrap">
										<span>Расход в Директе</span>
										<x-overlay.tooltip>
											Расход подтягивается из Директа
										</x-overlay.tooltip>
									</x-data.table-column>
								</x-data.table-columns>


								{{-- Данные --}}
								<x-data.table-rows>
									@foreach ($employee->clients as $client)
										@foreach ($client->channels as $channel)
											@php
												$rowspan = count($channel->revises) + 1;
											@endphp

											@foreach ($channel->revises as $revise)
												<x-data.table-row>
													{{-- Дата --}}
													<x-data.table-cell>
														{{ Str::ucfirst($revise->date->locale('ru_RU')->translatedFormat('F Y')) }}
													</x-data.table-cell>

													@if ($loop->index === 0)
														{{-- Клиент --}}
														<x-data.table-cell class="!tw-text-center" rowspan="{{ $rowspan }}">
															{{ $client->name }}
														</x-data.table-cell>

														{{-- Канал --}}
														<x-data.table-cell class="!tw-text-center" rowspan="{{ $rowspan }}">
															{{ $channel->name }}
														</x-data.table-cell>
													@endif

													{{-- Поступления --}}
													<x-data.table-cell class="!tw-text-center tw-text-green-500">
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


													</x-data.table-cell>

													<x-data.table-cell class="!tw-text-center">
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
													</x-data.table-cell>

													<x-data.table-cell class="!tw-text-center tw-text-purple-500">
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
                        )">
															+
														</a>
													</x-data.table-cell>

													<x-data.table-cell class="!tw-text-center">
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
                        )">
															+
														</a>
													</x-data.table-cell>

													<x-data.table-cell class="!tw-text-center tw-text-purple-500">
														{{ $revise->outcome === '-' ? '-' : Number::currency($revise->outcome, in: 'RUB') }}
													</x-data.table-cell>
												</x-data.table-row>
											@endforeach

											<x-data.table-row class="tw-font-bold">
												<x-data.table-cell class="!tw-text-right" colspan="3">
													Итого
												</x-data.table-cell>

												<x-data.table-cell class="!tw-text-center tw-text-green-500">
													{{ $channel->income === '-' ? '-' : Number::currency($channel->income, in: 'RUB') }}
												</x-data.table-cell>

												<x-data.table-cell class="!tw-text-center">
													{{ $channel->credit === '-' ? '-' : Number::currency($channel->credit, in: 'RUB') }}
												</x-data.table-cell>

												<x-data.table-cell class="!tw-text-center tw-text-purple-500">
													@if ($channel->cabinetReplenishment === '-')
														-
													@else
														<span class="tw-text-xs">{{ Number::currency($channel->cabinetReplenishment, in: 'RUB') }}</span>
														<span
															class="tw-text-sm">({{ Number::currency($channel->cabinetReplenishment - $channel->income, in: 'RUB') }})</span>
													@endif
												</x-data.table-cell>

												<x-data.table-cell class="!tw-text-center">
													<span class="tw-text-xs">{{ Number::currency($channel->workActsSum, in: 'RUB') }}</span>
													<span
														class="tw-text-sm">({{ Number::currency($channel->workActsSum - $channel->income, in: 'RUB') }})</span>
												</x-data.table-cell>

												<x-data.table-cell class="!tw-text-center tw-text-purple-500">
													@if ($channel->outcome === '-')
														-
													@else
														<span class="tw-text-xs">{{ Number::currency($channel->outcome, in: 'RUB') }}</span>
														<span class="tw-text-sm">({{ Number::currency($channel->outcome - $channel->income, in: 'RUB') }})</span>
													@endif
												</x-data.table-cell>
											</x-data.table-row>
										@endforeach
									@endforeach

								</x-data.table-rows>

								{{-- Итого по сотруднику --}}
								<x-data.table-row class="tw-bg-green-200">
									<x-data.table-cell class="!tw-text-right">
										Итого по сотруднику
									</x-data.table-cell>

									<x-data.table-cell colspan="2"></x-data.table-cell>

									<x-data.table-cell class="!tw-text-center tw-font-bold">
										{{ $employee->income === 0 ? 0 : Number::currency($employee->income, in: 'RUB') }}
									</x-data.table-cell>

									<x-data.table-cell class="!tw-text-center tw-font-bold">
										{{ $employee->credit === 0 ? 0 : Number::currency($employee->credit, in: 'RUB') }}
									</x-data.table-cell>

									<x-data.table-cell class="!tw-text-center tw-font-bold">
										{{ $employee->cabinetReplenishment === 0 ? 0 : Number::currency($employee->cabinetReplenishment, in: 'RUB') }}
									</x-data.table-cell>

									<x-data.table-cell class="!tw-text-center tw-font-bold">
										{{ $employee->workActsSum === 0 ? 0 : Number::currency($employee->workActsSum, in: 'RUB') }}
									</x-data.table-cell>

									<x-data.table-cell class="!tw-text-center tw-font-bold">
										{{ $employee->outcome === '-' ? '-' : Number::currency($employee->outcome, in: 'RUB') }}
									</x-data.table-cell>
								</x-data.table-row>
							@endforeach
						</tbody>
					</x-data.table>
				</x-panel.scroll-panel>
			@endif
		</div>
	</div>
</div>
