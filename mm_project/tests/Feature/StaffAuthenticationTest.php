<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('signs in an administrator and signs them out', function () {
    $staff = User::factory()->administrator()->create();
    $this->get(route('login'))->assertOk()->assertSee('Staff Login');

    $this->post(route('login.store'), ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirect(route('members.index'))->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($staff);

    $this->get(route('login'))->assertRedirect('/members');
    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('rejects an incorrect password without logging in', function () {
    $staff = User::factory()->administrator()->create();
    $this->from(route('login'))
        ->post(route('login.store'), ['email' => $staff->email, 'password' => 'wrong'])
        ->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('does not allow regular users into staff login', function () {
    $user = User::factory()->create();
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('requires email and password', function () {
    $this->post(route('login.store'), [])->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest();
});

it('limits repeated login attempts', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), [
            'email' => 'rate-limit@example.com', 'password' => 'wrong',
        ])->assertSessionHasErrors('email');
    }

    $this->post(route('login.store'), [
        'email' => 'rate-limit@example.com', 'password' => 'wrong',
    ])->assertStatus(429);
});

it('creates a staff account with a hashed password through the setup command', function () {
    $this->artisan('staff:create')
        ->expectsQuestion('Staff name', 'Class Administrator')
        ->expectsQuestion('Staff email', 'staff@example.com')
        ->expectsQuestion('Password (at least 12 characters)', 'Private-test-password')
        ->expectsQuestion('Confirm password', 'Private-test-password')
        ->expectsOutput('Staff account created. You can now sign in at /login.')
        ->assertSuccessful();

    $user = User::where('email', 'staff@example.com')->firstOrFail();
    expect($user->is_admin)->toBeTrue();
    expect($user->password)->not->toBe('Private-test-password');
    expect(Hash::check('Private-test-password', $user->password))->toBeTrue();
});
