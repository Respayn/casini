<?php

namespace App\Models;

use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property string $name
 * @property string $domain
 * @property int $client_id
 * @property int $specialist_id
 * @property int $manager_id
 * @property int $department_id
 * @property int $project_type_id
 * @property string $service_type
 * @property string $kpi
 * @property string $is_internal
 * @property string $is_active
 * @property string $traffic_attribution
 * @property string $metrika_counter
 * @property string $metrika_targets
 * @property string $google_ads_client_id
 * @property string $contract_number
 * @property string $additional_contract_number
 * @property string $recomendation_url
 * @property string $legal_entity
 * @property string $inn
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Client $client
 * @property User $specialist
 * @property User $manager
 * @property Department $department
 * @property ProjectType $projectType
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
        'project_type_id',
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

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ProjectStatusHistory::class);
    }
}
