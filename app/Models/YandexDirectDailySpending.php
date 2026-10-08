<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YandexDirectDailySpending extends Model
{
    protected $table = 'yandex_direct_daily_spendings';

    protected $fillable = [
        'project_id',
        'date',
        'cost_with_vat',
        'cost_without_vat',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'cost_with_vat' => 'decimal:2',
            'cost_without_vat' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
