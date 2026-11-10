<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const METHODS = ['Cash', 'Bank transfer', 'E-wallet'];

    protected $fillable = ['submission_key', 'membership_transaction_id', 'recorded_by', 'amount', 'method', 'paid_on', 'external_reference', 'notes'];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->reference = 'PAY-'.Str::uuid();
        });
        static::created(function (Payment $payment): void {
            $payment->reference = sprintf('PAY-%s-%06d', $payment->created_at->format('Y'), $payment->id);
            $payment->saveQuietly();
        });
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_on' => 'immutable_date', 'voided_at' => 'immutable_datetime'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class, 'membership_transaction_id');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
