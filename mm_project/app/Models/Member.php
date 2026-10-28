<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'address',
        'membership_category_id', 'membership_level_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Member $member): void {
            // Reserve a unique value until the database assigns the member's ID.
            $member->membership_number = 'MEM-'.Str::upper((string) Str::uuid());
        });

        static::created(function (Member $member): void {
            $member->membership_number = sprintf('MEM-%s-%06d', $member->created_at->format('Y'), $member->getKey());
            $member->saveQuietly();
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }
}
