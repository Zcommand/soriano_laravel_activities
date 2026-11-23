<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->benefits() as $name => $benefits) {
            DB::table('membership_levels')
                ->where('name', $name)
                ->where(function (Builder $query) use ($name): void {
                    $query->whereNull('benefits')->orWhereRaw("TRIM(benefits) = ''");

                    if ($name === 'Basic') {
                        $query->orWhere('benefits', 'default');
                    }

                    if ($name === 'Platinum') {
                        $query->orWhere('benefits', 'VIP');
                    }
                })
                ->update(['benefits' => $benefits]);
        }
    }

    public function down(): void
    {
        // Preserve benefit descriptions, which staff may already have reviewed or edited.
    }

    /** @return array<string, string> */
    private function benefits(): array
    {
        return [
            'Basic' => "Member updates and general announcements.\nAccess to regular member activities.",
            'Silver' => "All Basic benefits.\nPriority activity registration.\nAccess to selected workshops.",
            'Gold' => "All Silver benefits.\nAccess to exclusive member activities.\nPriority assistance.",
            'Platinum' => "All Gold benefits.\nFirst priority for limited-capacity activities.\nInvitations to special member events.",
        ];
    }
};
