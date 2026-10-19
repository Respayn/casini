<?php

use App\Livewire\Concerns\WithSidebarProjectFilter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Application\Reports\Download\DownloadReportFileQuery;
use Src\Application\Reports\Download\DownloadReportFileQueryHandler;
use Src\Application\Reports\GetList\GetReportsListQuery;
use Src\Application\Reports\GetList\GetReportsListQueryHandler;
use Src\Presentation\Livewire\Traits\WithColumnSettings;

new
    #[Title('Casini - Отчеты')]
    class extends Component
    {
        use WithColumnSettings;
        use WithSidebarProjectFilter;

        protected function getTableId(): string
        {
            return 'reports';
        }

        protected function getUserId(): int
        {
            return Auth::id();
        }

        protected function getDefaultColumns(): array
        {
            return [
                ['key' => 'date', 'label' => 'Дата'],
                ['key' => 'template', 'label' => 'Шаблон'],
                ['key' => 'client', 'label' => 'Клиент'],
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'channel', 'label' => 'Канал'],
                ['key' => 'project', 'label' => 'Клиенто-проект'],
                ['key' => 'period', 'label' => 'Период'],
                ['key' => 'specialist', 'label' => 'Специалист'],
                ['key' => 'format', 'label' => 'Формат'],
                ['key' => 'download', 'label' => 'Скачать'],
            ];
        }

        public ?int $pendingDownloadId = null;

        public bool $showInactiveProjects = false;

        public Carbon $periodFrom;

        public Carbon $periodTo;

        public array $columnSettings = [];

        /** Снимок настроек до открытия модалки */
        public array $columnSettingsSnapshot = [];

        #[Computed]
        public function canCreateReports(): bool
        {
            $user = Auth::user();

            return $user !== null
                && ($user->can('edit reports') || $user->can('full reports'));
        }

        public function mount()
        {
            if (Session::has('download')) {
                $this->pendingDownloadId = Session::get('download');
            }

            $this->periodFrom = Carbon::now()->startOfMonth()->startOfDay();
            $this->periodTo = $this->periodFrom->copy();
        }

        public function updatedPeriodFrom(): void
        {
            $this->clampPeriodToPresent();
        }

        public function updatedPeriodTo(): void
        {
            $this->clampPeriodToPresent();
        }

        /**
         * Нормализация: начало месяца, не позже текущего месяца, periodFrom <= periodTo.
         */
        private function clampPeriodToPresent(): void
        {
            $currentMonth = Carbon::now()->startOfMonth()->startOfDay();

            $this->periodFrom = $this->periodFrom->copy()->startOfMonth()->startOfDay();
            $this->periodTo = $this->periodTo->copy()->startOfMonth()->startOfDay();

            if ($this->periodFrom->greaterThan($currentMonth)) {
                $this->periodFrom = $currentMonth->copy();
            }

            if ($this->periodTo->greaterThan($currentMonth)) {
                $this->periodTo = $currentMonth->copy();
            }

            if ($this->periodFrom->greaterThan($this->periodTo)) {
                $this->periodTo = $this->periodFrom->copy();
            }
        }

        #[Computed]
        public function reports(): array
        {
            return app(GetReportsListQueryHandler::class)->handle(new GetReportsListQuery(
                $this->showInactiveProjects,
                $this->periodFrom->copy(),
                $this->periodTo->copy()->endOfMonth(),
                Auth::id(),
                $this->sidebarProjectId,
            ));
        }

        protected function afterSidebarProjectFilterChanged(): void
        {
            unset($this->reports);
        }

        public function download(DownloadReportFileQueryHandler $queryHandler, int $reportId)
        {
            $query = new DownloadReportFileQuery($reportId);
            $file = $queryHandler->handle($query);

            return response()->download($file->path, $file->name);
        }
    };
