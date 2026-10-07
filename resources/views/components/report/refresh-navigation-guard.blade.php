@props([
    'modalName' => 'report-data-refresh-leave-guard',
])

<div
    x-data="{
        pendingUrl: null,
        refreshInProgress: false,
        navigateHandler: null,
        beforeUnloadHandler: null,
        onRefreshStarted: null,
        onRefreshFinished: null,
        modalName: @js($modalName),

        init() {
            this.onRefreshStarted = () => {
                this.refreshInProgress = true;
            };

            this.onRefreshFinished = () => {
                this.refreshInProgress = false;
            };

            window.addEventListener('report-data-refresh-started', this.onRefreshStarted);
            window.addEventListener('report-data-refresh-finished', this.onRefreshFinished);

            this.navigateHandler = (event) => {
                if (! this.refreshInProgress) {
                    return;
                }

                event.preventDefault();
                this.pendingUrl = event.detail.url.href;
                this.$dispatch('modal-show', { name: this.modalName });
            };

            this.beforeUnloadHandler = (event) => {
                if (! this.refreshInProgress) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            };

            document.addEventListener('livewire:navigate', this.navigateHandler);
            window.addEventListener('beforeunload', this.beforeUnloadHandler);
        },

        destroy() {
            window.removeEventListener('report-data-refresh-started', this.onRefreshStarted);
            window.removeEventListener('report-data-refresh-finished', this.onRefreshFinished);

            if (this.navigateHandler) {
                document.removeEventListener('livewire:navigate', this.navigateHandler);
            }

            if (this.beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            }
        },

        waitForRefresh() {
            this.pendingUrl = null;
            this.$dispatch('modal-hide', { name: this.modalName });
        },

        cancelRefreshAndLeave() {
            const url = this.pendingUrl;
            this.pendingUrl = null;
            this.refreshInProgress = false;

            if (typeof $wire.$cancel === 'function') {
                $wire.$cancel();
            }

            this.$dispatch('modal-hide', { name: this.modalName });

            if (url) {
                if (window.Livewire?.navigate) {
                    window.Livewire.navigate(url);
                } else {
                    window.location.href = url;
                }
            }

            $wire.cancelReportDataRefresh();
        },

        onLeaveGuardHidden() {
            this.pendingUrl = null;
        },
    }"
    x-on:modal-hide.window="
        if ($event.detail.name !== modalName) return;
        onLeaveGuardHidden();
    "
    x-on:report-data-refresh-finished.window="refreshInProgress = false"
>
    {{ $slot }}

    <x-overlay.modal :name="$modalName" title="Выйти без обновления данных?">
        <x-slot:body>
            <div class="flex flex-col gap-3">
                <x-button.button
                    label="Дождаться обновления"
                    variant="primary"
                    x-on:click="waitForRefresh()"
                />
                <x-button.button
                    label="Отменить обновление"
                    x-on:click="cancelRefreshAndLeave()"
                />
            </div>
        </x-slot:body>
    </x-overlay.modal>
</div>
