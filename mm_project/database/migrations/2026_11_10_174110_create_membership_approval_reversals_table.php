<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->foreignId('previous_membership_level_id')->nullable()->constrained('membership_levels')->restrictOnDelete();
        });
        Schema::create('membership_approval_reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('membership_transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('reversed_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('previous_status');
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_approval_reversals');
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->dropForeign(['previous_membership_level_id']);
            $table->dropColumn('previous_membership_level_id');
        });
    }
};
