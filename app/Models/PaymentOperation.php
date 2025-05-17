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
 *
 * @property Payment $payment
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
        'payment_id'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'bank_received_amount' => 'float',
        'cabinet_top_up_amount' => 'float'
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
