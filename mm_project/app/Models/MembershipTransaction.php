<?php

namespace App\Models;

use Database\Factories\MembershipTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MembershipTransaction extends Model
{
    /** @use HasFactory<MembershipTransactionFactory> */
    use HasFactory;

    public const STATUSES = ['Pending', 'Paid', 'Approved', 'Expired', 'Cancelled'];

    protected $fillable = [
        'submission_key', 'member_id', 'membership_level_id', 'membership_category_id',
        'created_by', 'approved_by', 'type', 'status', 'member_name', 'membership_number',
        'level_name', 'category_name', 'fee', 'validity_days', 'benefits', 'notes',
        'cancellation_reason', 'approved_at', 'starts_at', 'expires_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (MembershipTransaction $transaction): void {
            $transaction->reference = 'TXN-'.Str::uuid();
        });
        static::created(function (MembershipTransaction $transaction): void {
            $transaction->reference = sprintf('TXN-%s-%06d', $transaction->created_at->format('Y'), $transaction->id);
            $transaction->saveQuietly();
        });
    }

    protected function casts(): array
    {
        return ['fee' => 'decimal:2', 'validity_days' => 'integer', 'approved_at' => 'immutable_datetime', 'starts_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function minorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function paidMinorUnits(): int
    {
        return $this->payments->sum(fn (Payment $payment): int => self::minorUnits($payment->amount));
    }

    public function balanceMinorUnits(): int
    {
        return max(0, self::minorUnits($this->fee) - $this->paidMinorUnits());
    }

    public function displayStatus(): string
    {
        return $this->status === 'Approved' && $this->expires_at?->isPast() ? 'Expired' : $this->status;
    }

    public static function expireDue(): int
    {
        return self::where('status', 'Approved')->where('expires_at', '<=', now())->update(['status' => 'Expired']);
    }
}
