<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('membership_levels', function (Blueprint $table): void {
            $table->decimal('fee', 10, 2)->default(0);
        });

        foreach (['Basic' => 200, 'Silver' => 400, 'Gold' => 600, 'Platinum' => 1000] as $name => $fee) {
            DB::table('membership_levels')->where('name', $name)->update(['fee' => $fee]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_levels', function (Blueprint $table): void {
            $table->dropColumn('fee');
        });
    }
};
