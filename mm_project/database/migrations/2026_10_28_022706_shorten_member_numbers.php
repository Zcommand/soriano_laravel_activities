<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (DB::table('members')->orderBy('id')->get(['id', 'membership_number', 'created_at']) as $member) {
                if (! preg_match('/^MEM-[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $member->membership_number)) {
                    continue;
                }

                DB::table('members')->where('id', $member->id)->update([
                    'membership_number' => sprintf('MEM-%s-%06d', Carbon::parse($member->created_at)->format('Y'), $member->id),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Original UUID numbers cannot be reconstructed; retain the readable numbers.
    }
};
