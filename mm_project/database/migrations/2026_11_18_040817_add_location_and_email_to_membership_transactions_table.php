<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('applicant_email')->nullable();
            $table->timestamp('application_email_queued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude', 'applicant_email', 'application_email_queued_at']);
        });
    }
};
