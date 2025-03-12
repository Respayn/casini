<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $domain
 * @property int $client_id
 * @property int $specialist_id
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
 * @property $recommendation_url
 * @property $legal_entity
 * @property $inn
 * @property $created_at
 * @property $updated_at
 *
 * @property Client $client
 * @property User $specialist
 * @property User $manager
 * @property Department $department
 * @property Collection<ProjectFieldHistory> $fieldHistories
 */
class Project extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'client_id',
        'specialist_id',
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
        'recommendation_url',
        'legal_entity',
        'inn',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'project_type' => ProjectType::class,
        'service_type' => ServiceType::class,
    ];

    /**
     * Связанный клиент.
     *
     * @return BelongsTo
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Связанные помощники.
     *
     * @return BelongsTo
     */
    public function assistants()
    {
        return $this->belongsTo(User::class, 'specialist_id');
    }

    /**
     * Связанные специалисты.
     *
     * @return BelongsTo
     */
    public function specialist()
    {
        return $this->belongsTo(User::class, 'specialist_id');
    }

    /**
     * Связанные отделы.
     *
     * @return BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Связанные записи об изменении полей.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fieldHistories()
    {
        return $this->hasMany(ProjectFieldHistory::class);
    }

    /**
     * Связанные регионы продвижения.
     *
     * @return BelongsToMany
     */
    public function promotionRegions(): BelongsToMany
    {
        return $this->belongsToMany(PromotionRegion::class);
    }

    /**
     * Связанные тематики продвижения.
     *
     * @return BelongsToMany
     */
    public function promotionTopics(): BelongsToMany
    {
        return $this->belongsToMany(PromotionTopic::class);
    }
}
