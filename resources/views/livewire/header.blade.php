<div class="w-full rounded-bl-2xl bg-white py-[10px] pe-[20px] ps-[15px]">
    <div class="flex items-center justify-between">
        <div>
            {{-- <x-v2.forms.select :options="[
                [ 'label' => 'СайтАктив (№12345)', 'value' => '2' ]
            ]" /> --}}
        </div>
        <div class="flex items-center">
            <x-button.button
                href="{{ route('system-settings.dictionaries') }}"
                icon="icons.gear"
                variant="gray"
                class="settings-button"
                rounded
                wire:navigate
            />

            <div class="ml-6 flex">
                <x-misc.skeleton
                    shape="circle"
                    size="40px"
                />
                <div class="ml-2.5">
                    <div class="font-semibold">{{ Auth::user()->name }}</div>
                    <div></div>
                </div>
            </div>
        </div>
    </div>
</div>
