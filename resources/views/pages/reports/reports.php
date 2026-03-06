<?php

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
use Src\Application\Reports\Update\UpdateReportCommand;
use Src\Application\Reports\Update\UpdateReportCommandHandler;
use Src\Presentation\Livewire\Traits\WithColumnSettings;

new
    #[Title('Casini - Отчеты')]
    class extends Component
    {
        use WithColumnSettings;

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
                ['key' => 'is_ready', 'label' => 'Отчет готов?'],
                ['key' => 'is_accepted', 'label' => 'Отчет принят менеджером?'],
                ['key' => 'is_sent', 'label' => 'Отчет отправлен клиенту?'],
            ];
        }

        public ?int $pendingDownloadId = null;
        public bool $showInactiveProjects = false;
        public Carbon $periodFrom;
        public Carbon $periodTo;

        public array $columnSettings = [];

        /** Снимок настроек до открытия модалки */
        public array $columnSettingsSnapshot = [];

        public function mount()
        {
            if (Session::has('download')) {
                $this->pendingDownloadId = Session::get('download');
            }

            $this->periodFrom = Carbon::now()->subMonth()->startOfMonth();
            $this->periodTo = Carbon::now()->endOfMonth()->startOfDay();
        }

        public function updatedPeriodFrom()
        {
            $this->periodFrom = $this->periodFrom->startOfMonth();
        }

        public function updatedPeriodTo()
        {
            $this->periodTo = $this->periodTo->endOfMonth()->startOfDay();
        }

        #[Computed]
        public function reports(): array
        {
            return app(GetReportsListQueryHandler::class)->handle(new GetReportsListQuery(
                $this->showInactiveProjects,
                $this->periodFrom,
                $this->periodTo
            ));
        }

        public function download(DownloadReportFileQueryHandler $queryHandler, int $reportId)
        {
            $query = new DownloadReportFileQuery($reportId);
            $file = $queryHandler->handle($query);

            return response()->download($file->path, $file->name);
        }

        public function updateIsReady(UpdateReportCommandHandler $command, int $reportId, bool $value)
        {
            $command->handle(new UpdateReportCommand(
                reportId: $reportId,
                isReady: $value
            ));
        }

        public function updateIsAccepted(UpdateReportCommandHandler $command, int $reportId, bool $value)
        {
            $command->handle(new UpdateReportCommand(
                reportId: $reportId,
                isAccepted: $value
            ));
        }

        public function updateIsSent(UpdateReportCommandHandler $command, int $reportId, bool $value)
        {
            $command->handle(new UpdateReportCommand(
                reportId: $reportId,
                isSent: $value
            ));
        }
    };
