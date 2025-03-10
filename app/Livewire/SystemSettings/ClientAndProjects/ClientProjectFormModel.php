<?php

namespace App\Livewire\SystemSettings\ClientAndProjects;

use App\Livewire\Forms\SystemSettings\ClientAndProjects\CreateClientProjectForm;
use App\Models\Project;
use App\Models\ProjectStatusHistory;
use App\Services\ClientService;
use App\Services\DepartmentService;
use App\Services\PromotionRegionService;
use App\Services\PromotionTopicService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ClientProjectFormModel extends Component
{
    public CreateClientProjectForm $clientProjectForm;

    private DepartmentService $departmentService;
    private ClientService $clientService;
    private PromotionRegionService $promotionRegionService;
    private PromotionTopicService $promotionTopicService;

    public Collection $departments;
    public Collection $clients;
    public Collection $promotionRegions;
    public Collection $promotionTopics;

    public function boot(
        DepartmentService $departmentService,
        ClientService $clientService,
        PromotionRegionService $promotionRegionService,
        PromotionTopicService $promotionTopicService,
    )
    {
        $this->departmentService = $departmentService;
        $this->clientService = $clientService;
        $this->promotionRegionService = $promotionRegionService;
        $this->promotionTopicService = $promotionTopicService;
    }

    public function mount($projectId = null)
    {
        $this->departments = $this->departmentService->getDepartments();
        $this->clients = $this->clientService->getClients();
        $this->promotionRegions = $this->promotionRegionService->getPromotionRegions();
        $this->promotionTopics = $this->promotionTopicService->getPromotionTopics();

        if ($projectId) {
            $project = Project::with(['assistants', 'promotionRegions', 'promotionTopics'])->findOrFail($projectId);

            // Заполняем свойства формы из модели проекта
            $this->clientProjectForm->id = $project->id;
            $this->clientProjectForm->name = $project->name;
            $this->clientProjectForm->domain = $project->domain;
            $this->clientProjectForm->client = $project->client_id;
            $this->clientProjectForm->specialist = $project->specialist_id;
            $this->clientProjectForm->manager = $project->manager_id;
            $this->clientProjectForm->department = $project->department_id;
            $this->clientProjectForm->projectType = $project->project_type;
//            $this->clientProjectForm->service_type = $project->service_type;
            $this->clientProjectForm->kpi = $project->kpi;
            $this->clientProjectForm->is_internal = $project->is_internal;
            $this->clientProjectForm->is_active = $project->is_active;

            $this->clientProjectForm->assistants = $project->assistants->pluck('id')->toArray();
            $this->clientProjectForm->promotionRegions = $project->promotionRegions->pluck('id')->toArray();
            $this->clientProjectForm->promotionTopics = $project->promotionTopics->pluck('id')->toArray();
        } else {
            $this->clientProjectForm->is_active = true;
            $this->clientProjectForm->promotionRegions[] = null;
            $this->clientProjectForm->promotionTopics[] = null;
        }
    }

    public function render()
    {
        return view('livewire.system-settings.client-and-projects.client-project-form');
    }

    public function addRegion()
    {
        $this->clientProjectForm->promotionRegions[] = null;
    }

    public function removeRegion($index)
    {
        unset($this->clientProjectForm->promotionRegions[$index]);
        $this->clientProjectForm->promotionRegions = array_values($this->clientProjectForm->promotionRegions);
    }

    public function addTopic()
    {
        $this->clientProjectForm->promotionTopics[] = null;
    }

    public function removeTopic($index)
    {
        unset($this->clientProjectForm->promotionTopics[$index]);
        $this->clientProjectForm->promotionTopics = array_values($this->clientProjectForm->promotionTopics);
    }

    public function save()
    {
        $this->clientProjectForm->validate();

        DB::beginTransaction();

        try {
            /** @var Project $project */
            if (isset($this->clientProjectForm->id)) {
                $project = Project::findOrFail($this->clientProjectForm->id);
            } else {
                $project = new Project();
            }

            $originalStatus = $project->is_active;

            $project->fill([
                'name' => $this->clientProjectForm->name,
                'domain' => $this->clientProjectForm->domain ?? null,
                'client_id' => $this->clientProjectForm->client,
                'specialist_id' => $this->clientProjectForm->specialist ?? null,
                'manager_id' => $this->clientProjectForm->manager ?? null,
                'department_id' => $this->clientProjectForm->department,
                'project_type' => $this->clientProjectForm->projectType ?? null,
                'kpi' => $this->clientProjectForm->kpi,
                'is_active' => $this->clientProjectForm->is_active ?? true,
                'is_internal' => $this->clientProjectForm->is_internal ?? false,
                'traffic_attribution' => $this->clientProjectForm->traffic_attribution ?? null,
                'metrika_counter' => $this->clientProjectForm->metrika_counter ?? null,
                'metrika_targets' => $this->clientProjectForm->metrika_targets ?? null,
                'google_ads_client_id' => $this->clientProjectForm->google_ads_client_id ?? null,
                'contract_number' => $this->clientProjectForm->contract_number ?? null,
                'additional_contract_number' => $this->clientProjectForm->additional_contract_number ?? null,
                'recomendation_url' => $this->clientProjectForm->recomendation_url ?? null,
                'legal_entity' => $this->clientProjectForm->legal_entity ?? null,
                'inn' => $this->clientProjectForm->inn ?? null,
            ]);

            $project->save();

            // TODO: Синхронизация помощников
//            if (!empty($this->clientProjectForm->assistants)) {
//                $assistantIds = collect($this->clientProjectForm->assistants)->filter()->all();
//                $project->assistants()->sync($assistantIds);
//            } else {
//                $project->assistants()->detach();
//            }
//
            // Синхронизация регионов продвижения
            if (!empty($this->clientProjectForm->promotionRegions)) {
                $promotionRegionIds = collect($this->clientProjectForm->promotionRegions)->filter()->all();
                $project->promotionRegions()->sync($promotionRegionIds);
            } else {
                $project->promotionRegions()->detach();
            }

            // Синхронизация тематик продвижения
            if (!empty($this->clientProjectForm->promotionTopics)) {
                $promotionTopicIds = collect($this->clientProjectForm->promotionTopics)->filter()->all();
                $project->promotionTopics()->sync($promotionTopicIds);
            } else {
                $project->promotionTopics()->detach();
            }

            if ($originalStatus != $project->is_active) {
                ProjectStatusHistory::query()->insert([
                    'project_id' => $project->id,
                    'changed_by' => auth()->id(),
                    'changed_at' => now(),
                    'field' => 'is_active',
                    'old_value' => $originalStatus,
                    'new_value' => $project->is_active,
                ]);
            }

            DB::commit();

            // Перенаправление или другие действия
            return redirect()->route('system-settings.clients-and-projects');
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
