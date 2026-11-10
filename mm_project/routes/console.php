<?php

use App\Models\MembershipTransaction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('memberships:expire', function (): void {
    $this->info(MembershipTransaction::expireDue().' membership transactions expired.');
})->purpose('Mark approved memberships whose validity has ended as expired');

Schedule::command('memberships:expire')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
