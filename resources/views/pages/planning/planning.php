<?php

use App\Livewire\Concerns\WithSidebarProjectFilter;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Src\Planning\Application\ProjectPlanService;

new
    #[Title('Casini - Планирование')]
    class extends Component
    {
        use WithSidebarProjectFilter;

        public int $year;

        /** Год, за который сейчас загружена таблица (для отката при несохранённых правках). */
        public int $loadedYear;

        public array $tableData = [];

        public bool $hasChanges = false;

        public array $modifiedProjectIds = [];

        /** Счётчик для принудительного remount дочерних plan-value после discard. */
        public int $dataEpoch = 0;

        /** navigate | year | null */
        public ?string $leaveGuardIntent = null;

        public ?string $pendingNavigateUrl = null;

        public ?int $pendingYear = null;

        private ProjectPlanService $projectPlanService;

        public function boot(ProjectPlanService $projectPlanService): void
        {
            $this->projectPlanService = $projectPlanService;
        }

        public function mount(\App\Support\SidebarProjectContext $context): void
        {
            $this->sidebarProjectId = $context->get();
            $this->year = Carbon::now()->year;
            $this->loadedYear = $this->year;
            $this->loadTableData();
        }

        public function loadTableData()
        {
            $this->tableData = $this->projectPlanService->getPlansForYear(
                $this->year,
                $this->sidebarProjectId,
            );
        }

        protected function afterSidebarProjectFilterChanged(): void
        {
            if ($this->hasChanges) {
                // Фильтр сайдбара уже сменился — сбрасываем черновик, иначе данные «чужого» фильтра смешаются.
                $this->resetDraftState();
            }

            $this->loadTableData();
        }

        public function updatedYear(int $value): void
        {
            if ($this->hasChanges && $value !== $this->loadedYear) {
                $this->pendingYear = $value;
                $this->year = $this->loadedYear;
                $this->leaveGuardIntent = 'year';
                $this->pendingNavigateUrl = null;
                $this->dispatch('modal-show', name: 'planning-leave-guard');

                return;
            }

            $this->applyYearChange();
        }

        #[On('project-plan-updated')]
        public function updateProjectPlan(int $rowIndex, array $parameters, int $month)
        {
            foreach ($this->tableData[$rowIndex]['parameters'] as $index => $param) {
                if (empty($param['is_calculated'])) {
                    $this->tableData[$rowIndex]['parameters'][$index]['plans'][$month] = $parameters[$index]['plans'][$month];
                }
            }

            $this->tableData[$rowIndex] = $this->projectPlanService->recalculateRow(
                $this->tableData[$rowIndex],
                $this->year,
                $month
            );

            $this->dispatch(
                "row-{$rowIndex}-updated",
                parameters: $this->tableData[$rowIndex]['parameters']
            );

            $projectId = $this->tableData[$rowIndex]['project_id'];
            $this->modifiedProjectIds[$projectId] = true;
            $this->hasChanges = true;
        }

        public function updatedTableData($value, $key)
        {
            $parts = explode('.', $key);
            if (isset($parts[0]) && isset($this->tableData[$parts[0]])) {
                $rowIndex = (int) $parts[0];
                $projectId = $this->tableData[$rowIndex]['project_id'];
                $this->modifiedProjectIds[$projectId] = true;
            }

            $this->hasChanges = true;
        }

        public function save()
        {
            $plansToSave = array_filter($this->tableData, function ($plan) {
                return isset($this->modifiedProjectIds[$plan['project_id']]);
            });

            if (! empty($plansToSave)) {
                $this->projectPlanService->savePlansForYear($this->year, $plansToSave);
            }

            $this->modifiedProjectIds = [];
            $this->hasChanges = false;
        }

        public function discardChanges(): void
        {
            $this->resetDraftState();
            $this->loadTableData();
        }

        /**
         * Сохранить черновик и продолжить отложенный уход / смену года.
         */
        public function saveAndContinue(?string $url = null): void
        {
            $this->save();
            $this->finishLeaveGuard($url);
        }

        /**
         * Отбросить черновик и продолжить отложенный уход / смену года.
         */
        public function discardAndContinue(?string $url = null): void
        {
            $this->discardChanges();
            $this->finishLeaveGuard($url);
        }

        public function cancelLeaveGuard(): void
        {
            if ($this->leaveGuardIntent === null && $this->pendingYear === null) {
                return;
            }

            $this->leaveGuardIntent = null;
            $this->pendingYear = null;
            $this->pendingNavigateUrl = null;
            $this->year = $this->loadedYear;
        }

        private function finishLeaveGuard(?string $url = null): void
        {
            $intent = $this->leaveGuardIntent;
            $pendingYear = $this->pendingYear;
            $navigateUrl = $url ?? $this->pendingNavigateUrl;

            $this->leaveGuardIntent = null;
            $this->pendingYear = null;
            $this->pendingNavigateUrl = null;

            $this->dispatch('modal-hide', name: 'planning-leave-guard');

            if ($intent === 'year' && $pendingYear !== null) {
                $this->year = $pendingYear;
                $this->applyYearChange();

                return;
            }

            if (filled($navigateUrl)) {
                $this->js('Livewire.navigate('.json_encode($navigateUrl).')');
            }
        }

        private function applyYearChange(): void
        {
            $this->resetDraftState();
            $this->loadedYear = $this->year;
            $this->loadTableData();
        }

        private function resetDraftState(): void
        {
            $this->modifiedProjectIds = [];
            $this->hasChanges = false;
            $this->dataEpoch++;
        }

        #[Computed]
        public function canEditPlanValues(): bool
        {
            return Auth::user()->hasAnyPermission([
                'edit planning',
                'full planning',
            ]);
        }

        #[Computed]
        public function canViewApprovals(): bool
        {
            return Auth::user()->hasAnyPermission([
                'read planning approval',
                'edit planning approval',
                'full planning approval',
            ]);
        }

        #[Computed]
        public function canEditApprovals(): bool
        {
            return Auth::user()->hasAnyPermission([
                'edit planning approval',
                'full planning approval',
            ]);
        }
    };
