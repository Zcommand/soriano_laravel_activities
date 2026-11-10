<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipApprovalReversal extends Model
{
    protected $fillable = ['reversed_by', 'approved_by', 'approved_at', 'starts_at', 'expires_at', 'previous_status', 'reason'];

    protected function casts(): array
    {
        return ['approved_at' => 'immutable_datetime', 'starts_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class, 'membership_transaction_id');
    }
}
