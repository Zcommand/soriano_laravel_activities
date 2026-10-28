<?php

namespace App\Models;

use Database\Factories\MembershipLevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipLevel extends Model
{
    /** @use HasFactory<MembershipLevelFactory> */
    use HasFactory;

    protected $fillable = ['name', 'fee'];

    protected function casts(): array
    {
        return ['fee' => 'decimal:2'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'membership_level_id');
    }
}
