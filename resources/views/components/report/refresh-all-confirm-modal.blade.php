<x-overlay.modal name="refresh-all-projects-confirm" title="Хотите обновить все клиенто-проекты?">
    <x-slot:body>
        <div class="max-w-lg">
            <p class="text-primary-text">
                Обновление всех клиенто-проектов может занять продолжительное время.
                <span class="font-bold">Если хотите обновить один клиенто-проект, выберите его (отфильтруйте) в виджете слева.</span>
            </p>
            <div class="mt-6 flex flex-wrap justify-between gap-4">
                <x-button.button
                    label="Обновить все клиенто-проекты"
                    variant="primary"
                    x-on:click="$dispatch('modal-hide', { name: 'refresh-all-projects-confirm' }); $wire.refreshAllData()"
                />
                <x-button.button
                    label="Отменить"
                    x-on:click="$dispatch('modal-hide', { name: 'refresh-all-projects-confirm' })"
                />
            </div>
        </div>
    </x-slot:body>
</x-overlay.modal>
