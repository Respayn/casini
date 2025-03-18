<?php

namespace App\Livewire\SystemSettings\ClientAndProjects;

use App\Livewire\Forms\SystemSettings\ClientAndProjects\CreateClientProjectForm;
use App\Models\Project;
use App\Models\ProjectFieldHistory;
use App\Services\ClientService;
use App\Services\DepartmentService;
use App\Services\PromotionRegionService;
use App\Services\PromotionTopicService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

// TODO: Сделать получение данных и сохранение через сервисный слой
class ClientProjectFormModel extends Component
{
    public CreateClientProjectForm $clientProjectForm;

    private ClientService $clientService;
    private PromotionRegionService $promotionRegionService;
    private PromotionTopicService $promotionTopicService;

    public Collection $clients;
    public Collection $promotionRegions;
    public Collection $promotionTopics;

    public function boot(
        ClientService $clientService,
        PromotionRegionService $promotionRegionService,
        PromotionTopicService $promotionTopicService,
    )
    {
        $this->clientService = $clientService;
        $this->promotionRegionService = $promotionRegionService;
        $this->promotionTopicService = $promotionTopicService;
    }

    public function mount($projectId = null)
    {
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
            $this->clientProjectForm->manager = $project->client->manager_id;
            $this->clientProjectForm->projectType = $project->project_type;
//            $this->clientProjectForm->service_type = $project->service_type;
            $this->clientProjectForm->kpi = $project->kpi;
            $this->clientProjectForm->isInternal = $project->isInternal;
            $this->clientProjectForm->isActive = $project->isActive;

            $this->clientProjectForm->assistants = $project->assistants->pluck('id')->toArray();
            $this->clientProjectForm->promotionRegions = $project->promotionRegions->pluck('id')->toArray();
            $this->clientProjectForm->promotionTopics = $project->promotionTopics->pluck('id')->toArray();
        } else {
            $this->clientProjectForm->isActive = true;
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
                'project_type' => $this->clientProjectForm->projectType ?? null,
                'kpi' => $this->clientProjectForm->kpi,
                'is_active' => $this->clientProjectForm->isActive ?? true,
                'is_internal' => $this->clientProjectForm->isInternal ?? false,
                'traffic_attribution' => $this->clientProjectForm->trafficAttribution ?? null,
                'metrika_counter' => $this->clientProjectForm->metrikaCounter ?? null,
                'metrika_targets' => $this->clientProjectForm->metrikaTargets ?? null,
                'google_ads_client_id' => $this->clientProjectForm->googleAdsClientId ?? null,
                'contract_number' => $this->clientProjectForm->contractNumber ?? null,
                'additional_contract_number' => $this->clientProjectForm->additionalContractNumber ?? null,
                'recommendation_url' => $this->clientProjectForm->recommendationUrl ?? null,
                'legal_entity' => $this->clientProjectForm->legalEntity ?? null,
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
                ProjectFieldHistory::query()->insert([
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
