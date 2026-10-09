<?php

namespace App\Models;

use App\Enums\AdvertisingSystem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $order
 * @property string|null $invoice_number
 * @property Carbon|null $invoice_date
 * @property float $bank_received_amount
 * @property float $cabinet_top_up_amount
 * @property string|null $payment_details
 * @property float $credit_amount
 * @property bool $fee_included
 * @property float $fee_amount
 * @property float $included_fee_debt
 * @property float $ad_cabinet_amount
 * @property Carbon|null $ad_cabinet_sent_date
 * @property bool $is_sent_to_cabinet
 * @property Carbon|null $status_changed_at
 * @property int|null $status_changed_by
 * @property bool $is_fee_in_piggy_bank
 * @property bool $is_invoice_issued
 * @property Carbon|null $invoice_changed_at
 * @property int|null $invoice_changed_by
 * @property AdvertisingSystem|null $advertising_system
 * @property int|null $project_id
 * @property int|null $manager_id
 * @property string|null $comment
 * @property Carbon|null $opened_at
 * @property Carbon|null $unprocessed_notified_at
 * @property Payment $payment
 * @property Project|null $project
 * @property User|null $manager
 * @property User|null $statusChangedBy
 * @property User|null $invoiceChangedBy
 */
class PaymentOperation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order',
        'invoice_number',
        'invoice_date',
        'bank_received_amount',
        'cabinet_top_up_amount',
        'payment_details',
        'payment_id',
        'credit_amount',
        'fee_included',
        'fee_amount',
        'included_fee_debt',
        'ad_cabinet_amount',
        'ad_cabinet_sent_date',
        'is_sent_to_cabinet',
        'status_changed_at',
        'status_changed_by',
        'is_fee_in_piggy_bank',
        'is_invoice_issued',
        'invoice_changed_at',
        'invoice_changed_by',
        'advertising_system',
        'project_id',
        'manager_id',
        'comment',
        'opened_at',
        'unprocessed_notified_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'bank_received_amount' => 'float',
        'cabinet_top_up_amount' => 'float',
        'credit_amount' => 'float',
        'fee_included' => 'boolean',
        'fee_amount' => 'float',
        'included_fee_debt' => 'float',
        'ad_cabinet_amount' => 'float',
        'ad_cabinet_sent_date' => 'date',
        'is_sent_to_cabinet' => 'boolean',
        'status_changed_at' => 'datetime',
        'is_fee_in_piggy_bank' => 'boolean',
        'is_invoice_issued' => 'boolean',
        'invoice_changed_at' => 'datetime',
        'advertising_system' => AdvertisingSystem::class,
        'opened_at' => 'datetime',
        'unprocessed_notified_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function invoiceChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invoice_changed_by');
    }
}
