<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Application\Reports\Download\DownloadReportFileQuery;
use Src\Application\Reports\Download\DownloadReportFileQueryHandler;
use Src\Application\Reports\Generate\GenerateReportCommand;
use Src\Application\Reports\Generate\GenerateReportCommandHandler;
use Src\Application\Reports\GetFormData\GetReportFormDataQuery;
use Src\Application\Reports\GetFormData\GetReportFormDataQueryHandler;

new #[Title('Casini - Создать отчет')] class extends Component {

    public ?int $projectId = null;
    public Carbon $from;
    public Carbon $to;
    public ?string $format = null;
    public ?int $templateId = null;

    public function mount(): void
    {
        $this->from = Carbon::now()->subMonth();
        $this->to = Carbon::now();

        if (!empty($this->formData->formats)) {
            $this->format = array_first($this->formData->formats)['value'];
        }

        if (!empty($this->formData->templates)) {
            $this->templateId = array_first($this->formData->templates)['value'];
        }
    }

    #[Computed]
    public function formData()
    {
        return app(GetReportFormDataQueryHandler::class)
            ->handle(new GetReportFormDataQuery());
    }

    public function create(): void
    {
        $this->validate([
            'projectId' => 'required',
            'format' => 'required',
            'templateId' => 'required'
        ], [
            'projectId.required' => 'Выберите проект',
            'format.required' => 'Выберите формат',
            'templateId.required' => 'Выберите шаблон'
        ]);

        $this->generateReport();

        $this->redirectRoute('reports');
    }

    public function createAndDownload()
    {
        $this->validate([
            'projectId' => 'required',
            'format' => 'required',
            'templateId' => 'required'
        ], [
            'projectId.required' => 'Выберите проект',
            'format.required' => 'Выберите формат',
            'templateId.required' => 'Выберите шаблон'
        ]);

        $reportId = $this->generateReport();

        $file = app(DownloadReportFileQueryHandler::class)->handle(
            new DownloadReportFileQuery($reportId)
        );

        return response()->download(
            $file->path,
            $file->name
        );
    }

    private function generateReport(): int
    {
        return app(GenerateReportCommandHandler::class)->handle(
            new GenerateReportCommand(
                $this->projectId,
                DateTimeImmutable::createFromInterface($this->from),
                DateTimeImmutable::createFromInterface($this->to),
                $this->format,
                $this->templateId,
                Auth::user()->id
            )
        );
    }
};
