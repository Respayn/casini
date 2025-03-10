<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property int $client_id
 * @property int $specialist_id
 * @property int $manager_id
 * @property int $department_id
 * @property ProjectType $project_type
 * @property ServiceType $service_type
 * @property $kpi
 * @property $is_internal
 * @property $is_active
 * @property $traffic_attribution
 * @property $metrika_counter
 * @property $metrika_targets
 * @property $google_ads_client_id
 * @property $contract_number
 * @property $additional_contract_number
 * @property $recomendation_url
 * @property $legal_entity
 * @property $inn
 * @property $created_at
 * @property $updated_at
 *
 * @property Client $client
 * @property User $specialist
 * @property User $manager
 * @property Department $department
 * @property Collection<ProjectStatusHistory> $statusHistories
 */
class Project extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'client_id',
        'specialist_id',
        'manager_id',
        'department_id',
        'project_type',
        'service_type',
        'kpi',
        'is_internal',
        'is_active',
        'traffic_attribution',
        'metrika_counter',
        'metrika_targets',
        'google_ads_client_id',
        'contract_number',
        'additional_contract_number',
        'recomendation_url',
        'legal_entity',
        'inn',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'project_type' => ProjectType::class,
        'service_type' => ServiceType::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function specialist()
    {
        return $this->belongsTo(User::class, 'specialist_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectStatusHistory::class);
    }

    public function promotionRegions(): BelongsToMany
    {
        return $this->belongsToMany(PromotionRegion::class);
    }

    public function promotionTopics(): BelongsToMany
    {
        return $this->belongsToMany(PromotionTopic::class);
    }
}
