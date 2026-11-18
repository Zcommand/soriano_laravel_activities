<?php

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Services\MembershipWorkflow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.openweather.key' => 'server-weather-secret', 'services.google_maps.key' => 'browser-maps-key']);
});

function applicationIntegrationPayload(Member $member, array $overrides = []): array
{
    return array_replace([
        'member_id' => $member->id, 'membership_level_id' => $member->membership_level_id,
        'type' => 'Application', 'submission_key' => (string) Str::uuid(),
        'latitude' => '14.5995000', 'longitude' => '120.9842000',
    ], $overrides);
}

it('saves selected coordinates and emails the member rather than the staff or a supplied address', function () {
    Notification::fake();
    $staff = User::factory()->administrator()->create();
    $member = Member::factory()->create();
    $data = applicationIntegrationPayload($member, ['applicant_email' => 'attacker@example.test']);
    $this->actingAs($staff)->post(route('transactions.store'), $data)->assertSessionHasNoErrors();
    $transaction = MembershipTransaction::sole();
    expect($transaction->latitude)->toBe('14.5995000')->and($transaction->longitude)->toBe('120.9842000')
        ->and($transaction->applicant_email)->toBe($member->email)->and($transaction->application_email_queued_at)->not->toBeNull();
    Notification::assertSentOnDemand(MembershipApplicationSubmitted::class, fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === $member->email && $notification->application->id === $transaction->id
    );
    $this->post(route('transactions.store'), $data)->assertSessionHasNoErrors();
    Notification::assertCount(1);
    $this->assertDatabaseCount('membership_transactions', 1);
    $this->get(route('transactions.show', $transaction))->assertOk()->assertSee('14.5995000')->assertSee('Application email queued');
});

it('only places the email job in the queue after the outer transaction commits', function () {
    config(['queue.default' => 'database']);
    $staff = User::factory()->administrator()->create();
    $member = Member::factory()->create();
    DB::beginTransaction();
    try {
        app(MembershipWorkflow::class)->create(applicationIntegrationPayload($member), $staff);
        $this->assertDatabaseCount('jobs', 0);
        DB::commit();
    } catch (Throwable $exception) {
        DB::rollBack();
        throw $exception;
    }
    $this->assertDatabaseCount('jobs', 1);
    $this->assertDatabaseCount('membership_transactions', 1);
});

it('does not queue mail for a transaction that rolls back', function () {
    config(['queue.default' => 'database']);
    $staff = User::factory()->administrator()->create();
    $member = Member::factory()->create();
    DB::beginTransaction();
    app(MembershipWorkflow::class)->create(applicationIntegrationPayload($member), $staff);
    DB::rollBack();
    $this->assertDatabaseCount('jobs', 0);
    $this->assertDatabaseCount('membership_transactions', 0);
});

it('rejects invalid or incomplete coordinates before saving or notifying', function (array $overrides) {
    Notification::fake();
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    $this->post(route('transactions.store'), applicationIntegrationPayload($member, $overrides))->assertSessionHasErrors();
    $this->assertDatabaseCount('membership_transactions', 0);
    Notification::assertNothingSent();
})->with([
    [['latitude' => '91']], [['longitude' => '-181']],
    [['latitude' => '', 'longitude' => '120']], [['latitude' => '14', 'longitude' => '']],
]);

it('does not send the application notification for a renewal', function () {
    Notification::fake();
    $this->actingAs(User::factory()->administrator()->create());
    $member = Member::factory()->create();
    MembershipTransaction::factory()->create(['member_id' => $member->id, 'status' => 'Approved', 'starts_at' => now()->subDay(), 'expires_at' => now()->addYear()]);
    $this->post(route('transactions.store'), applicationIntegrationPayload($member, ['type' => 'Renewal']))->assertSessionHasNoErrors();
    Notification::assertNothingSent();
});

it('renders a useful email without exposing staff links or API keys', function () {
    $transaction = MembershipTransaction::factory()->create();
    $notification = new MembershipApplicationSubmitted($transaction);
    $html = $notification->toMail(new stdClass)->render()->toHtml();
    expect($notification)->toBeInstanceOf(ShouldQueue::class);
    expect($html)->toContain($transaction->reference)->toContain('Payment and approval are recorded separately')
        ->not->toContain('server-weather-secret')->not->toContain('/transactions/');
});

it('fetches metric weather through Laravel and caches repeated coordinates', function () {
    $this->actingAs(User::factory()->administrator()->create());
    Http::fake(['api.openweathermap.org/*' => Http::response([
        'name' => 'Manila', 'main' => ['temp' => 29.5, 'humidity' => 76],
        'weather' => [['description' => 'scattered clouds']], 'wind' => ['speed' => 2.2], 'dt' => 1790000000,
    ])]);
    $url = route('weather.show', ['lat' => '14.5995', 'lon' => '120.9842']);
    $this->getJson($url)->assertOk()->assertJsonPath('place', 'Manila')->assertJsonPath('temperature', 29.5)->assertJsonMissing(['appid' => 'server-weather-secret']);
    $this->getJson($url)->assertOk();
    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => $request['units'] === 'metric' && $request['appid'] === 'server-weather-secret');
});

it('protects weather access and validates coordinates', function () {
    $url = route('weather.show', ['lat' => 14, 'lon' => 120]);
    $this->getJson($url)->assertUnauthorized();
    $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
    $this->actingAs(User::factory()->administrator()->create())->getJson(route('weather.show', ['lat' => 91, 'lon' => 120]))->assertUnprocessable();
    Http::assertNothingSent();
});

it('keeps provider errors and keys out of weather responses', function (int $status) {
    $this->actingAs(User::factory()->administrator()->create());
    Http::fake(['*' => Http::response(['message' => 'server-weather-secret'], $status)]);
    $response = $this->getJson(route('weather.show', ['lat' => 14, 'lon' => 120]));
    $response->assertStatus(503)->assertDontSee('server-weather-secret');
})->with([401, 429, 500]);

it('handles missing configuration and connection failures gracefully', function () {
    $this->actingAs(User::factory()->administrator()->create());
    config(['services.openweather.key' => '']);
    $url = route('weather.show', ['lat' => 14, 'lon' => 120]);
    $this->getJson($url)->assertStatus(503);
    Http::assertNothingSent();
    config(['services.openweather.key' => 'server-weather-secret']);
    Http::fake(fn () => throw new ConnectionException('provider unavailable'));
    $this->getJson($url)->assertStatus(503)->assertDontSee('server-weather-secret');
});

it('renders the Maps browser key and defaults without exposing the weather secret', function () {
    $this->actingAs(User::factory()->administrator()->create());
    Member::factory()->create();
    $this->get(route('transactions.create'))->assertOk()->assertSee('browser-maps-key')->assertSee('14.5995')->assertSee('120.9842')->assertSee('Application location')->assertDontSee('server-weather-secret');
});

it('shows the fixed office widget alongside recent transactions without exposing the weather key', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $this->get(route('dashboard'))->assertOk()->assertSee('Company location')->assertSee('Manila, Philippines')
        ->assertSee('data-fixed-location', false)->assertSee('14.5995')->assertSee('120.9842')
        ->assertSee('Recent transactions')->assertDontSee('server-weather-secret');
});
