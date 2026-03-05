<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order
 * @property string|null $invoice_number
 * @property Carbon|null $invoice_date
 * @property float $bank_received_amount
 * @property float $cabinet_top_up_amount
 * @property string|null $payment_details
 * @property string|null $payment_description
 *
 * @property bool $was_opened
 * @property Carbon|null $ad_cabinet_money_send_date
 * @property float $credit_amount
 *
 * @property int|null $channel_id
 * @property float|null $cabinet_received_amount
 * @property int|null $manager_id
 * @property int|null $client_project_id
 * @property int|null $created_by
 *
 * @property bool $sent_to_ad_cabinet
 * @property Carbon|null $sent_to_ad_cabinet_updated_at
 * @property int|null $sent_to_ad_cabinet_updated_by
 *
 * @property bool $invoice_issued
 * @property Carbon|null $invoice_issued_updated_at
 * @property int|null $invoice_issued_updated_by
 *
 * @property float|null $fee
 * @property bool $fee_included
 * @property Carbon|null $fee_included_updated_at
 * @property int|null $fee_included_updated_by
 *
 * @property bool $include_fee_from_other_payments
 *
 * @property int $payment_id
 *
 * @property Payment $payment
 * @property Channel|null $channel
 * @property Manager|null $manager
 * @property Project|null $clientProject
 * @property User|null $creator
 * @property User|null $sentToAdCabinetUpdatedBy
 * @property User|null $invoiceIssuedUpdatedBy
 * @property User|null $feeIncludedUpdatedBy
 */
class PaymentOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order',
        'invoice_number',
        'invoice_date',
        'bank_received_amount',
        'cabinet_top_up_amount',
        'payment_details',
        'payment_description',
        'payment_id',

        'was_opened',
        'ad_cabinet_money_send_date',
        'credit_amount',

        'channel_id',
        'cabinet_received_amount',
        'manager_id',
        'client_project_id',
        'created_by',

        'sent_to_ad_cabinet',
        'sent_to_ad_cabinet_updated_at',
        'sent_to_ad_cabinet_updated_by',

        'invoice_issued',
        'invoice_issued_updated_at',
        'invoice_issued_updated_by',

        'fee',
        'fee_included',
        'fee_included_updated_at',
        'fee_included_updated_by',

        'include_fee_from_other_payments',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'bank_received_amount' => 'float',
        'cabinet_top_up_amount' => 'float',

        'was_opened' => 'bool',
        'ad_cabinet_money_send_date' => 'date',
        'credit_amount' => 'float',

        'cabinet_received_amount' => 'float',

        'sent_to_ad_cabinet' => 'bool',
        'sent_to_ad_cabinet_updated_at' => 'datetime',

        'invoice_issued' => 'bool',
        'invoice_issued_updated_at' => 'datetime',

        'fee' => 'float',
        'fee_included' => 'bool',
        'fee_included_updated_at' => 'datetime',

        'include_fee_from_other_payments' => 'bool',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    public function clientProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'client_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sentToAdCabinetUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_to_ad_cabinet_updated_by');
    }

    public function invoiceIssuedUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invoice_issued_updated_by');
    }

    public function feeIncludedUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fee_included_updated_by');
    }
}
