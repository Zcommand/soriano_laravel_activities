<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('membership_levels')->whereIn('name', ['Basic', 'Silver', 'Gold', 'Platinum'])->whereNull('validity_days')->update(['validity_days' => 365]);
    }

    public function down(): void
    {
        // Preserve configured validity values when reverting code.
    }
};
