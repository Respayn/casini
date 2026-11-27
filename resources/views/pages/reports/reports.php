<?php

use App\Enums\ChannelReportGrouping;
use App\Livewire\Concerns\WithSidebarProjectFilter;
use App\Support\SharedProductFilters;
use App\Support\SidebarProjectAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session as LivewireSession;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Application\Reports\Delete\DeleteReportCommand;
use Src\Application\Reports\Delete\DeleteReportCommandHandler;
use Src\Application\Reports\Download\DownloadReportFileQuery;
use Src\Application\Reports\Download\DownloadReportFileQueryHandler;
use Src\Application\Reports\GetList\GetReportsListQuery;
use Src\Application\Reports\GetList\GetReportsListQueryHandler;
use Src\Domain\Reports\ReportRepositoryInterface;
use Src\Presentation\Livewire\Traits\WithColumnSettings;

new
    #[Title('Отчеты')]
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
                ['key' => 'channel', 'label' => 'Тип клиенто-проекта'],
                ['key' => 'project', 'label' => 'Клиенто-проект'],
                ['key' => 'period', 'label' => 'Период'],
                ['key' => 'specialist', 'label' => 'Специалист'],
                ['key' => 'format', 'label' => 'Формат'],
                ['key' => 'download', 'label' => 'Действия'],
            ];
        }

        public ?int $pendingDownloadId = null;

        /** @var list<string> */
        public array $generationWarnings = [];

        public bool $showInactiveProjects = false;

        public Carbon $periodFrom;

        public Carbon $periodTo;

        /**
         * Группировка списка: none | clients | project_type (как в Каналах).
         * Для «типа» - полное имя типа клиенто-проекта (channelGroupLabel).
         */
        #[LivewireSession(key: 'reports_list_grouping')]
        public string $grouping = 'none';

        /** Черновик группировки в модалке «Настроить отчет». */
        public string $groupingDraft = 'none';

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

        #[Computed]
        public function canDeleteReports(): bool
        {
            $user = Auth::user();

            return $user !== null && $user->can('full reports');
        }

        public function mount()
        {
            if (Session::has('download')) {
                $this->pendingDownloadId = Session::get('download');
            }

            if (Session::has('report_generation_warnings')) {
                $warnings = Session::get('report_generation_warnings');
                $this->generationWarnings = is_array($warnings)
                    ? array_values(array_filter($warnings, static fn ($item): bool => is_string($item) && $item !== ''))
                    : [];
            }

            $this->periodFrom = Carbon::now()->startOfMonth()->startOfDay();
            $this->periodTo = $this->periodFrom->copy();
            $this->showInactiveProjects = SharedProductFilters::resolveShowInactive($this->showInactiveProjects);
            $this->sanitizeGrouping();
            $this->groupingDraft = $this->grouping;
        }

        public function openReportSettings(): void
        {
            $this->sanitizeGrouping();
            $this->groupingDraft = $this->grouping;
        }

        public function applyReportGrouping(): void
        {
            $this->groupingDraft = $this->normalizeGrouping($this->groupingDraft);
            $this->grouping = $this->groupingDraft;
            unset($this->reportGroups, $this->isFlatGroupingView);
        }

        public function cancelReportSettings(): void
        {
            $this->groupingDraft = $this->grouping;
        }

        private function sanitizeGrouping(): void
        {
            $this->grouping = $this->normalizeGrouping($this->grouping);
        }

        private function normalizeGrouping(string $value): string
        {
            return match ($value) {
                ChannelReportGrouping::CLIENTS->value,
                ChannelReportGrouping::PROJECT_TYPE->value => $value,
                default => ChannelReportGrouping::NONE->value,
            };
        }

        #[Computed]
        public function isFlatGroupingView(): bool
        {
            return $this->sidebarProjectId !== null
                || $this->grouping === ChannelReportGrouping::NONE->value;
        }

        /**
         * @return list<array{label: ?string, rows: list<\Src\Application\Reports\GetList\ReportListItemDto>}>
         */
        #[Computed]
        public function reportGroups(): array
        {
            $reports = $this->reports;

            if ($this->isFlatGroupingView) {
                return [[
                    'label' => null,
                    'rows' => $reports,
                ]];
            }

            $grouped = [];
            foreach ($reports as $report) {
                $label = $this->grouping === ChannelReportGrouping::CLIENTS->value
                    ? $report->clientName
                    : $report->channelGroupLabel;
                $grouped[$label][] = $report;
            }

            $result = [];
            foreach ($grouped as $label => $rows) {
                $result[] = [
                    'label' => $label,
                    'rows' => $rows,
                ];
            }

            return $result;
        }

        public function updatedShowInactiveProjects(): void
        {
            SharedProductFilters::rememberShowInactive($this->showInactiveProjects);
            unset($this->reports, $this->reportGroups, $this->isFlatGroupingView);
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
            unset($this->reports, $this->reportGroups, $this->isFlatGroupingView);
        }

        public function download(DownloadReportFileQueryHandler $queryHandler, int $reportId)
        {
            $this->ensureCanAccessReport($reportId);

            $query = new DownloadReportFileQuery($reportId);
            $file = $queryHandler->handle($query);

            return response()->download($file->path, $file->name);
        }

        public function delete(DeleteReportCommandHandler $command, int $reportId): void
        {
            $this->ensureCanDeleteReports();
            $this->ensureCanAccessReport($reportId);
            $this->resetErrorBag();

            $command->handle(new DeleteReportCommand($reportId));
            unset($this->reports, $this->reportGroups, $this->isFlatGroupingView);
        }

        private function ensureCanDeleteReports(): void
        {
            abort_unless($this->canDeleteReports, 403, __('permissions.denied'));
        }

        private function ensureCanAccessReport(int $reportId): void
        {
            $report = app(ReportRepositoryInterface::class)->findById($reportId);

            abort_unless(
                SidebarProjectAccess::userCanAccessProject($report->getProjectId()),
                403,
                __('permissions.denied')
            );
        }
    };
