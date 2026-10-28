<?php

namespace App\Models;

use Database\Factories\MembershipCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipCategory extends Model
{
    /** @use HasFactory<MembershipCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'membership_category_id');
    }
}
