<?php

namespace App\Models;

use App\Enums\LaborRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitrix24DailyLabor extends Model
{
    protected $table = 'bitrix24_daily_labor';

    protected $fillable = [
        'project_id',
        'date',
        'report_month',
        'user_id',
        'role',
        'seconds',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'report_month' => 'date',
            'role' => LaborRole::class,
            'seconds' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
