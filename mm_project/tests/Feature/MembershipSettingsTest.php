<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restricts membership settings to administrators', function (string $kind) {
    $this->get(route($kind.'.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route($kind.'.index'))->assertForbidden();
    $this->post(route($kind.'.store'), ['name' => 'Unauthorized'])->assertForbidden();
})->with(['categories', 'levels']);

it('manages categories and preserves assigned records', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $this->get(route('categories.index'))->assertOk()->assertSee('Corporate');
    $this->get(route('categories.create'))->assertOk();
    $this->post(route('categories.store'), ['name' => ' Alumni '])->assertRedirect(route('categories.index'));
    $category = MembershipCategory::where('name', 'Alumni')->firstOrFail();
    $this->get(route('categories.edit', $category))->assertOk();
    $this->put(route('categories.update', $category), ['name' => 'Graduate'])->assertSessionHasNoErrors();
    expect($category->fresh()->name)->toBe('Graduate');
    $member = Member::factory()->create(['membership_category_id' => $category->id]);
    $this->delete(route('categories.destroy', $category))->assertSessionHasErrors('delete');
    $this->assertModelExists($category);
    $this->assertModelExists($member);
    $unused = MembershipCategory::factory()->create();
    $this->delete(route('categories.destroy', $unused))->assertRedirect(route('categories.index'));
    $this->assertModelMissing($unused);
});

it('rejects duplicate categories and retains the corporate company rule', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $this->post(route('categories.store'), ['name' => 'Student'])->assertSessionHasErrors('name');
    $corporate = MembershipCategory::where('name', 'Corporate')->firstOrFail();
    $this->put(route('categories.update', $corporate), ['name' => 'Business'])->assertSessionHasErrors('name');
    $this->put(route('categories.update', $corporate), ['name' => 'Corporate'])->assertSessionHasNoErrors();
});

it('manages level fees validity and benefits without changing member assignment', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $this->get(route('levels.index'))->assertOk()->assertSee('₱200.00')->assertSee('Not configured');
    $this->get(route('levels.create'))->assertOk();
    $data = ['name' => 'Executive', 'fee' => '1500.50', 'validity_days' => 365, 'benefits' => 'Priority assistance'];
    $this->post(route('levels.store'), $data)->assertRedirect(route('levels.index'));
    $level = MembershipLevel::where('name', 'Executive')->firstOrFail();
    $this->get(route('levels.edit', $level))->assertOk()->assertSee('Priority assistance');
    $member = Member::factory()->create(['membership_level_id' => $level->id]);
    $this->put(route('levels.update', $level), [...$data, 'fee' => '1600.00'])->assertSessionHasNoErrors();
    expect($level->fresh()->fee)->toBe('1600.00');
    expect($member->fresh()->membership_level_id)->toBe($level->id);
    $this->delete(route('levels.destroy', $level))->assertSessionHasErrors('delete');
    $this->assertModelExists($level);
    $unused = MembershipLevel::factory()->create();
    $this->delete(route('levels.destroy', $unused))->assertRedirect(route('levels.index'));
    $this->assertModelMissing($unused);
});

it('validates level details', function (array $changes, string $field) {
    $this->actingAs(User::factory()->administrator()->create());
    $this->post(route('levels.store'), array_replace(['name' => 'Executive', 'fee' => '500.00'], $changes))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'duplicate name' => [['name' => 'Basic'], 'name'],
    'negative fee' => [['fee' => '-1'], 'fee'],
    'extra decimals' => [['fee' => '1.234'], 'fee'],
    'zero validity' => [['validity_days' => 0], 'validity_days'],
    'fractional validity' => [['validity_days' => '2.5'], 'validity_days'],
    'long benefits' => [['benefits' => str_repeat('a', 5001)], 'benefits'],
]);

it('shows managed choices in member forms and escapes benefits', function () {
    $this->actingAs(User::factory()->administrator()->create());
    MembershipCategory::factory()->create(['name' => 'Alumni']);
    MembershipLevel::factory()->create(['name' => 'Executive', 'fee' => 1500, 'benefits' => '<script>alert(1)</script>']);
    $this->get(route('members.create'))->assertOk()->assertSee('Alumni')->assertSee('Executive -');
    $this->get(route('levels.index'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});
