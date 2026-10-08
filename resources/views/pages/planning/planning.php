<?php

use App\Livewire\Concerns\WithSidebarProjectFilter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Planning\Application\ProjectPlanService;

new
    #[Title('Casini - Планирование')]
    class extends Component
    {
        use WithSidebarProjectFilter;

        public int $year;

        /** Год, за который сейчас загружена таблица (для отката при несохранённых правках). */
        public int $loadedYear;

        /** Как на Каналах/Статистике: показывать неактивные клиенто-проекты. */
        public bool $showInactive = false;

        /**
         * Галочка «НДС»: показ/ввод бюджета, CPL, CPC с НДС 22% (в базе всегда без НДС).
         */
        public bool $includeVat = false;

        public array $tableData = [];

        public bool $hasChanges = false;

        public array $modifiedProjectIds = [];

        /** Счётчик для принудительного remount дочерних plan-value после discard. */
        public int $dataEpoch = 0;

        /**
         * Чем занята модалка «Выйти без сохранения?»:
         * navigate — уход на другую страницу / продукт;
         * year — смена года в таблице;
         * null — модалка не ждёт ответа.
         */
        public ?string $leaveGuardIntent = null;

        public ?string $pendingNavigateUrl = null;

        public ?int $pendingYear = null;

        private ProjectPlanService $projectPlanService;

        public function boot(ProjectPlanService $projectPlanService): void
        {
            $this->projectPlanService = $projectPlanService;
        }

        public function mount(): void
        {
            $this->year = Carbon::now()->year;
            $this->loadedYear = $this->year;
            $this->loadTableData();
        }

        public function loadTableData()
        {
            $this->tableData = $this->projectPlanService->getPlansForYear(
                $this->year,
                $this->sidebarProjectId,
                $this->showInactive,
            );
        }

        public function updatedShowInactive(): void
        {
            $this->reloadTableAfterFilterChange();
        }

        /**
         * Смена режима НДС не трогает tableData и не ставит hasChanges —
         * ячейки пересчитывают показ на клиенте.
         */
        public function updatedIncludeVat(): void
        {
            $this->js('window.dispatchEvent(new CustomEvent("planning-vat-sync"))');
            $this->skipRender();
        }

        private function reloadTableAfterFilterChange(): void
        {
            if ($this->hasChanges) {
                $this->resetDraftState();
            }

            $this->loadTableData();
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

            $this->syncRecalculatedRow($rowIndex, $month);
        }

        #[On('project-plan-cell-updated')]
        public function updatePlanCell(int $rowIndex, int $month, int $index, mixed $value): void
        {
            if (! $this->canEditPlanValues) {
                return;
            }

            if (! isset($this->tableData[$rowIndex]['parameters'][$index])) {
                return;
            }

            if (! empty($this->tableData[$rowIndex]['parameters'][$index]['is_calculated'])) {
                return;
            }

            $this->tableData[$rowIndex]['parameters'][$index]['plans'][$month] = $value;
            $this->syncRecalculatedRow($rowIndex, $month);
        }

        public function updatedTableData($value, $key)
        {
            $parts = explode('.', $key);

            if (preg_match('/^(\d+)\.approvals\.(\d+)\.approved$/', $key, $matches)) {
                $rowIndex = (int) $matches[1];
                $quarter = (int) $matches[2];
                $approved = (bool) $value;

                if ($approved) {
                    $today = Carbon::now();
                    $user = Auth::user();
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved'] = true;
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved_at'] = $today->toDateString();
                    $this->tableData[$rowIndex]['approvals'][$quarter]['date'] = $today->format('d.m.y');
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved_by'] = $user?->id;
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved_by_name'] = $user
                        ? trim($user->first_name.' '.$user->last_name)
                        : null;
                } else {
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved'] = false;
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved_at'] = null;
                    $this->tableData[$rowIndex]['approvals'][$quarter]['date'] = null;
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved_by'] = null;
                    $this->tableData[$rowIndex]['approvals'][$quarter]['approved_by_name'] = null;
                }

                // Подсветка ячейки и дата в Alpine — только через sync (без «залипшего» bg-primary в HTML).
                $this->js('window.dispatchEvent(new CustomEvent("planning-table-sync"))');
            }

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
            $previousIds = array_map('intval', array_column($this->tableData, 'project_id'));

            $this->modifiedProjectIds = [];
            $this->hasChanges = false;
            $this->loadedYear = $this->year;
            $this->loadTableData();

            $newIds = array_map('intval', array_column($this->tableData, 'project_id'));
            $sameRows = $previousIds === $newIds && $previousIds !== [];

            if ($sameRows) {
                $this->dispatchPlanningTableSync();
                $this->skipRender();
            } else {
                $this->dataEpoch++;
            }
        }

        private function dispatchPlanningTableSync(): void
        {
            $this->js('window.dispatchEvent(new CustomEvent("planning-table-sync"))');
        }

        private function syncRecalculatedRow(int $rowIndex, int $month): void
        {
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
