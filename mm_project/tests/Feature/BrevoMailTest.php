<?php

use App\Mail\BrevoTransport;
use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Services\MembershipWorkflow;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;

uses(DatabaseMigrations::class);

beforeEach(function () {
    config([
        'mail.default' => 'brevo', 'services.brevo.key' => 'test-private-brevo-key',
        'mail.from.address' => 'sender@example.test', 'mail.from.name' => 'Membership Management',
        'queue.default' => 'database',
    ]);
    Http::preventStrayRequests();
});

it('delivers the committed application queue job through Brevo with the existing email content', function () {
    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<accepted@example.test>'], 201)]);
    $staff = User::factory()->administrator()->create();
    $member = Member::factory()->create();
    $transaction = app(MembershipWorkflow::class)->create([
        'member_id' => $member->id, 'membership_level_id' => $member->membership_level_id,
        'type' => 'Application', 'submission_key' => (string) Str::uuid(),
    ], $staff);
    $this->assertDatabaseCount('jobs', 1);

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true, '--tries' => 1])->assertSuccessful();

    Http::assertSent(function (Request $request) use ($member, $transaction): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.brevo.com/v3/smtp/email'
            && $request->hasHeader('api-key', 'test-private-brevo-key')
            && $request['sender']['email'] === 'sender@example.test'
            && $request['to'][0]['email'] === $member->email
            && $request['subject'] === 'Membership application received'
            && str_contains($request['htmlContent'], $transaction->reference)
            && str_contains($request['textContent'], $transaction->reference)
            && ! str_contains($request['htmlContent'], 'test-private-brevo-key')
            && $request['headers']['IdempotencyKey'] === $transaction->submission_key;
    });
    $this->assertDatabaseCount('jobs', 0);
    $this->assertDatabaseCount('failed_jobs', 0);
});

it('does not silently lose a rejected queued email', function () {
    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['message' => 'sender not activated'], 403)]);
    $transaction = MembershipTransaction::factory()->create();
    Notification::route('mail', 'member@example.test')->notify(new MembershipApplicationSubmitted($transaction));

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

    $this->assertDatabaseHas('jobs', ['attempts' => 1]);
    Http::assertSentCount(1);
});

it('accepts an already processed retry without submitting a second logical message', function () {
    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::sequence()
        ->push(['messageId' => '<accepted@example.test>'], 201)
        ->push(['code' => 'duplicate_parameter', 'message' => 'Email for the idempotency key has already been processed'], 400)]);
    $notification = new MembershipApplicationSubmitted(MembershipTransaction::factory()->create());
    $recipient = Notification::route('mail', 'member@example.test');

    $recipient->notifyNow($notification);
    $recipient->notifyNow($notification);

    $requests = Http::recorded();
    expect($requests)->toHaveCount(2)
        ->and($requests[0][0]['headers']['IdempotencyKey'])->toBe($requests[1][0]['headers']['IdempotencyKey']);
});

it('reports API failures without exposing provider response bodies or secrets', function (int $status, array $body) {
    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response($body, $status)]);
    $email = (new Email)->from('sender@example.test')->to('member@example.test')->subject('Application')->text('Application received');

    expect(fn () => (new BrevoTransport('test-private-brevo-key'))->send($email))
        ->toThrow(TransportException::class, 'Brevo did not accept the email (HTTP '.$status.'). Check the sender, account activation, API permissions, and sending quota.');
})->with([
    'unauthorized' => [401, ['message' => 'secret provider details']],
    'quota exceeded' => [429, ['message' => 'secret provider details']],
    'provider error' => [500, ['message' => 'secret provider details']],
    'malformed success' => [201, []],
    'unrelated duplicate' => [400, ['code' => 'duplicate_parameter', 'message' => 'Sender already exists']],
]);

it('does not contact Brevo without an API key', function () {
    $email = (new Email)->from('sender@example.test')->to('member@example.test')->subject('Application')->text('Received');

    expect(fn () => (new BrevoTransport(''))->send($email))->toThrow(TransportException::class, 'Brevo is not configured.');
    Http::assertNothingSent();
});

it('turns connection failures into a safe retryable error', function () {
    Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::failedConnection()]);
    $email = (new Email)->from('sender@example.test')->to('member@example.test')->subject('Application')->text('Received');

    expect(fn () => (new BrevoTransport('test-private-brevo-key'))->send($email))
        ->toThrow(TransportException::class, 'Could not connect to Brevo. The queued email can be retried.');
});
