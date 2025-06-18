<?php

namespace App\Models;

use App\Enums\PaymentSource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $number
 * @property PaymentSource $source
 * @property Carbon $received_date
 * @property string|null $external_id
 * @property $client_id
 * @property $created_at
 * @property $updated_at
 * @property $deleted_at
 *
 * @property Client $client
 * @property PaymentOperation[] $operations
 */
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'source',
        'received_date',
        'external_id',
        'client_id'
    ];

    protected $casts = [
        'source' => PaymentSource::class,
        'received_date' => 'date'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(PaymentOperation::class);
    }
}
