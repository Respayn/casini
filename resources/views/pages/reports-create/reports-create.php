<?php

use App\Livewire\Concerns\WithSidebarProjectFilter;
use App\Support\SidebarProjectAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Application\Reports\Generate\GenerateReportCommand;
use Src\Application\Reports\Generate\GenerateReportCommandHandler;
use Src\Application\Reports\GetFormData\GetReportFormDataQuery;
use Src\Application\Reports\GetFormData\GetReportFormDataQueryHandler;
use Src\Application\Reports\GetFormData\ReportFormDataResponse;
use Src\Domain\Reports\ReportRepositoryInterface;

new
#[Title('Casini - Создать отчет')]
class extends Component
{
    use WithSidebarProjectFilter;

    public ?int $projectId = null;

    public Carbon $from;

    public Carbon $to;

    public ?string $format = null;

    public ?int $templateId = null;

    public ?int $editingReportId = null;

    public string $generateError = '';

    /** @var array{projectId: ?int, from: string, to: string, format: ?string, templateId: ?int} */
    public array $initialSnapshot = [];

    public function mount(?int $reportId = null): void
    {
        $this->ensureCanCreateReports();

        $currentMonth = Carbon::now()->startOfMonth()->startOfDay();
        $this->from = $currentMonth->copy();
        $this->to = $currentMonth->copy();

        $this->format = $this->formData->formats[0]['value'] ?? null;

        if ($reportId !== null) {
            $this->loadReportForEdit($reportId);
        } else {
            $this->applySidebarProjectSelection();
        }

        $this->captureSnapshot();
    }

    private function loadReportForEdit(int $reportId): void
    {
        $report = app(ReportRepositoryInterface::class)->findById($reportId);

        abort_unless(
            SidebarProjectAccess::userCanAccessProject($report->getProjectId()),
            403,
            __('permissions.denied')
        );

        $this->editingReportId = $report->getId();
        $this->projectId = $report->getProjectId();
        $this->from = Carbon::parse($report->getPeriodStart()->format('Y-m-d'))->startOfMonth()->startOfDay();
        $this->to = Carbon::parse($report->getPeriodEnd()->format('Y-m-d'))->startOfMonth()->startOfDay();
        $this->format = $report->getFormat()->value;
        $this->templateId = $report->getTemplateId();
    }

    protected function afterSidebarProjectFilterChanged(): void
    {
        unset($this->formData);
        unset($this->projectOptions);

        if ($this->editingReportId === null) {
            $this->applySidebarProjectSelection();
        }

        $this->generateError = '';
    }

    public function updatedFrom(): void
    {
        $this->clampPeriodToPresent();
        $this->generateError = '';
    }

    public function updatedTo(): void
    {
        $this->clampPeriodToPresent();
        $this->generateError = '';
    }

    public function updatedProjectId(): void
    {
        $this->generateError = '';
    }

    public function updatedFormat(): void
    {
        $this->generateError = '';
    }

    public function updatedTemplateId(): void
    {
        $this->generateError = '';
    }

    private function clampPeriodToPresent(): void
    {
        $currentMonth = Carbon::now()->startOfMonth()->startOfDay();

        $this->from = $this->from->copy()->startOfMonth()->startOfDay();
        $this->to = $this->to->copy()->startOfMonth()->startOfDay();

        if ($this->from->greaterThan($currentMonth)) {
            $this->from = $currentMonth->copy();
        }

        if ($this->to->greaterThan($currentMonth)) {
            $this->to = $currentMonth->copy();
        }

        if ($this->from->greaterThan($this->to)) {
            $this->to = $this->from->copy();
        }
    }

    private function applySidebarProjectSelection(): void
    {
        $availableIds = array_map(
            static fn (array $option): int => (int) $option['value'],
            $this->projectOptions,
        );

        if ($this->sidebarProjectId !== null
            && in_array($this->sidebarProjectId, $availableIds, true)
        ) {
            $this->projectId = $this->sidebarProjectId;

            return;
        }

        if ($this->projectId !== null && ! in_array($this->projectId, $availableIds, true)) {
            $this->projectId = null;
        }
    }

    /**
     * @return array{projectId: ?int, from: string, to: string, format: ?string, templateId: ?int}
     */
    private function currentSnapshot(): array
    {
        return [
            'projectId' => $this->projectId,
            'from' => $this->from->format('Y-m'),
            'to' => $this->to->format('Y-m'),
            'format' => $this->format,
            'templateId' => $this->templateId,
        ];
    }

    private function captureSnapshot(): void
    {
        $this->initialSnapshot = $this->currentSnapshot();
    }

    public function discardChanges(): void
    {
        $this->projectId = $this->initialSnapshot['projectId'] ?? null;
        $this->from = Carbon::createFromFormat('Y-m', $this->initialSnapshot['from'])
            ->startOfMonth()
            ->startOfDay();
        $this->to = Carbon::createFromFormat('Y-m', $this->initialSnapshot['to'])
            ->startOfMonth()
            ->startOfDay();
        $this->format = $this->initialSnapshot['format'] ?? null;
        $this->templateId = $this->initialSnapshot['templateId'] ?? null;
        $this->generateError = '';
        $this->resetErrorBag();
    }

    #[Computed]
    public function hasPendingChanges(): bool
    {
        if ($this->initialSnapshot === []) {
            return false;
        }

        return $this->currentSnapshot() !== $this->initialSnapshot;
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->editingReportId !== null;
    }

    #[Computed]
    public function canSubmit(): bool
    {
        return $this->projectId !== null
            && $this->templateId !== null
            && filled($this->format);
    }

    #[Computed]
    public function formData(): ReportFormDataResponse
    {
        return app(GetReportFormDataQueryHandler::class)->handle(
            new GetReportFormDataQuery(Auth::id()),
        );
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    #[Computed]
    public function projectOptions(): array
    {
        $projects = $this->formData->projects;

        if ($this->editingReportId !== null || $this->sidebarProjectId === null) {
            return $projects;
        }

        return array_values(array_filter(
            $projects,
            fn (array $option): bool => (int) $option['value'] === $this->sidebarProjectId,
        ));
    }

    #[Computed]
    public function noticeMessage(): ?string
    {
        if ($this->generateError !== '') {
            return $this->generateError;
        }

        if ($this->projectOptions === []) {
            return 'Нет доступных клиенто-проектов для создания отчета. Проверьте права или выбор в боковом меню.';
        }

        if ($this->formData->templates === []) {
            return 'Нет доступных шаблонов. Загрузите шаблон на странице «Шаблоны», затем вернитесь сюда.';
        }

        return null;
    }

    #[Computed]
    public function noticeVariant(): string
    {
        return $this->generateError !== '' ? 'error' : 'info';
    }

    public function create(GenerateReportCommandHandler $command): void
    {
        $this->ensureCanCreateReports();
        $this->generateError = '';

        $availableProjectIds = array_map(
            static fn (array $option): int => (int) $option['value'],
            $this->formData->projects,
        );

        $this->validate([
            'projectId' => ['required', 'integer', Rule::in($availableProjectIds)],
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'format' => 'required|string|in:docx',
            'templateId' => 'required|integer',
        ], [
            'projectId.required' => 'Выберите клиенто-проект',
            'projectId.in' => 'Выбранный клиенто-проект недоступен',
            'from.required' => 'Укажите начало периода',
            'to.required' => 'Укажите конец периода',
            'to.after_or_equal' => 'Конец периода не может быть раньше начала',
            'format.required' => 'Выберите формат',
            'format.in' => 'Недопустимый формат отчета',
            'templateId.required' => 'Выберите шаблон отчета',
        ]);

        if (! SidebarProjectAccess::userCanAccessProject((int) $this->projectId)) {
            abort(403, __('permissions.denied'));
        }

        $this->clampPeriodToPresent();

        try {
            $result = $command->handle(new GenerateReportCommand(
                $this->projectId,
                DateTimeImmutable::createFromInterface($this->from->copy()->startOfMonth()->startOfDay()),
                DateTimeImmutable::createFromInterface($this->to->copy()->endOfMonth()->endOfDay()),
                $this->format,
                $this->templateId,
                Auth::id(),
                $this->editingReportId,
            ));
        } catch (Throwable $e) {
            report($e);
            $this->generateError = 'Не удалось сформировать отчет. Попробуйте ещё раз или обратитесь к администратору.';

            return;
        }

        session()->flash('download', $result->reportId);

        if ($result->warnings !== []) {
            session()->flash('report_generation_warnings', $result->warnings);
        }

        $this->redirectRoute('reports');
    }

    private function ensureCanCreateReports(): void
    {
        $user = Auth::user();

        abort_unless(
            $user !== null && ($user->can('edit reports') || $user->can('full reports')),
            403,
            __('permissions.denied')
        );
    }
};
