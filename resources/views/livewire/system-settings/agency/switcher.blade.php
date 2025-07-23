<div class="relative min-w-[220px]">
    <div
        x-data="{
        open: false,
        select(value) {
            if (value === 'create') {
                Livewire.dispatch('modal-show', { name: 'agency-modal' });
            } else if (value) {
                $wire.call('changeAgency', value);
                this.open = false;
            }
        }
    }"
    >
        <div class="group relative">
            <div
                class="flex min-h-[42px] w-full items-center rounded-[5px] border pe-10 ps-4 border-input-border cursor-pointer"
                x-on:click="open = !open"
                :class="open ? 'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white' : 'rounded-[5px]'"
            >
                <span>
                    @php
                        $selectedAgency = collect($agencies)->firstWhere('id', $selectedAgencyId);
                    @endphp
                    {{ $selectedAgency ? ($selectedAgency['name'] . ' (№' . $selectedAgency['id'] . ')') : 'Выберите агентство' }}
                </span>
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                    <x-icons.arrow
                        class="transition-transform duration-300"
                        x-bind:class="{
                    'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': open,
                    'rounded-[5px]': !open
                }"
                    />
                </span>
            </div>
        </div>
        <div
            class="border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0 bg-white absolute z-100"
            x-cloak
            x-show="open"
            x-on:click.outside="open = false"
        >
            @foreach($agencies as $agency)
                <div
                    class="hover:bg-primary flex min-h-[42px] items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white {{ $selectedAgencyId == $agency['id'] ? 'font-bold bg-gray-50' : '' }}"
                    x-on:click="select('{{ $agency['id'] }}')"
                >
                    {{ $agency['name'] }} (№{{ $agency['id'] }})
                </div>
            @endforeach
{{--            <div class="border-t my-1"></div>--}}
{{--            <div--}}
{{--                class="font-bold text-blue-600 cursor-pointer hover:bg-blue-50 px-4 py-2"--}}
{{--                x-on:click="select('create')"--}}
{{--            >--}}
{{--                Создать агентство +--}}
{{--            </div>--}}
        </div>
    </div>
</div>
