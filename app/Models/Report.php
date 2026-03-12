<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Domain\Reports\ReportFormat;

class Report extends Model
{
    protected $casts = [
        'created_at' => 'immutable_datetime',
        'period_start' => 'immutable_datetime',
        'period_end' => 'immutable_datetime',
        'format' => ReportFormat::class
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specialist_id', 'id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }
}
