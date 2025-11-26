<?php

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
        public int $year;
        public array $tableData = [];
        public bool $hasChanges = false;
        public array $modifiedProjectIds = [];

        private ProjectPlanService $projectPlanService;

        public function boot(ProjectPlanService $projectPlanService): void
        {
            $this->projectPlanService = $projectPlanService;
        }

        public function mount(): void
        {
            $this->year = Carbon::now()->year;
            $this->loadTableData();
        }

        public function loadTableData()
        {
            $this->tableData = $this->projectPlanService->getPlansForYear($this->year);
        }

        public function updatedYear()
        {
            $this->modifiedProjectIds = [];
            $this->hasChanges = false;
            $this->loadTableData();
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

            if (!empty($plansToSave)) {
                $this->projectPlanService->savePlansForYear($this->year, $plansToSave);
            }

            $this->modifiedProjectIds = [];
            $this->hasChanges = false;
        }

        #[Computed]
        public function canEditPlanValues(): bool
        {
            return Auth::user()->hasAnyPermission([
                'edit planning',
                'full planning'
            ]);
        }

        #[Computed]
        public function canViewApprovals(): bool
        {
            return Auth::user()->hasAnyPermission([
                'read planning approval',
                'full planning approval'
            ]);
        }

        #[Computed]
        public function canEditApprovals(): bool
        {
            return Auth::user()->hasAnyPermission([
                'edit planning approval',
                'full planning approval'
            ]);
        }
    };
