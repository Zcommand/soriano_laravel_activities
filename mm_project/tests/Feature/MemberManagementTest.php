<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders the directory with labeled mobile records and working member actions', function () {
    $member = Member::factory()->create();
    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index'))->assertOk()
        ->assertSee('Member directory')
        ->assertSee('data-label="Membership number"', false)
        ->assertSee('data-label="Account status"', false)
        ->assertSee(route('members.show', $member))
        ->assertSee(route('members.edit', $member));
});

it('shows zero member totals and identifies the signed-in administrator', function () {
    $staff = User::factory()->administrator()->create(['name' => 'Jamie Staff']);
    $this->actingAs($staff)->get(route('members.index'))->assertOk()
        ->assertSee('Administrator')->assertSee('Jamie Staff')
        ->assertViewHas('summary', ['total' => 0, 'active' => 0, 'inactive' => 0])
        ->assertViewHas('levels', fn ($levels) => $levels->every(fn ($level) => $level->members_count === 0));
});

it('counts all accounts and levels independently of filters and pagination', function () {
    $levels = MembershipLevel::orderBy('id')->get()->keyBy('name');
    Member::factory()->count(11)->create(['membership_level_id' => $levels['Basic']->id]);
    Member::factory()->inactive()->create(['membership_level_id' => $levels['Silver']->id]);
    Member::factory()->create(['membership_level_id' => $levels['Gold']->id]);

    $this->actingAs(User::factory()->administrator()->create());
    foreach ([[], ['status' => 'inactive'], ['search' => 'no-such-person'], ['page' => 2]] as $filters) {
        $this->get(route('members.index', $filters))->assertOk()
            ->assertViewHas('summary', ['total' => 13, 'active' => 12, 'inactive' => 1])
            ->assertViewHas('levels', function ($levels): bool {
                return $levels->pluck('members_count', 'name')->all() === [
                    'Basic' => 11, 'Silver' => 1, 'Gold' => 1, 'Platinum' => 0,
                ];
            });
    }
});

it('updates the overview after deactivation and reactivation', function () {
    $member = Member::factory()->create();
    $this->actingAs(User::factory()->administrator()->create());
    $this->delete(route('members.destroy', $member))->assertRedirect();
    $this->get(route('members.index'))->assertOk()
        ->assertViewHas('summary', ['total' => 1, 'active' => 0, 'inactive' => 1]);

    $this->patch(route('members.activate', $member))->assertRedirect();
    $this->get(route('members.index'))->assertOk()
        ->assertViewHas('summary', ['total' => 1, 'active' => 1, 'inactive' => 0]);
});

it('provides theme controls and logo variants without adding navigation to login', function () {
    $this->get(route('login'))->assertOk()
        ->assertSee('logo-horizontal-light.png')
        ->assertDontSee('logo-horizontal-dark.png')
        ->assertSee('class="login-logo logo-dark"', false)
        ->assertSee('js/theme.js')->assertSee('data-theme-toggle', false)
        ->assertDontSee('workspace-sidebar')
        ->assertDontSee('aria-label="Main navigation"', false);

    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index'))->assertOk()
        ->assertSee('logo-horizontal-light.png')
        ->assertSee('class="workspace-sidebar"', false)
        ->assertSee('aria-controls="workspace-navigation"', false)
        ->assertSee('js/workspace.js')
        ->assertSee('aria-label="Main navigation"', false);
});

it('requires a valid company name when registering a Corporate member', function (mixed $company) {
    $corporate = MembershipCategory::where('name', 'Corporate')->firstOrFail();
    $this->actingAs(User::factory()->administrator()->create())
        ->post(route('members.store'), memberPayload([
            'membership_category_id' => $corporate->id,
            'company_name' => $company,
        ]))->assertSessionHasErrors('company_name');

    expect(Member::count())->toBe(0);
})->with([null, '', '   ', str_repeat('x', 256), [['invalid']]]);

it('stores and searches Corporate company names and displays them safely', function () {
    $corporate = MembershipCategory::where('name', 'Corporate')->firstOrFail();
    $this->actingAs(User::factory()->administrator()->create())
        ->post(route('members.store'), memberPayload([
            'membership_category_id' => $corporate->id,
            'company_name' => '  Harbor Services  ',
        ]))->assertSessionHasNoErrors()->assertRedirect();

    $member = Member::firstOrFail();
    expect($member->company_name)->toBe('Harbor Services');
    $this->get(route('members.index', ['search' => 'Harbor']))->assertOk()->assertSee($member->email);
    $this->get(route('members.show', $member))->assertOk()->assertSee('Company name')->assertSee('Harbor Services');
    $this->get(route('members.edit', $member))->assertOk()->assertSee('value="Harbor Services"', false);

    $member->update(['company_name' => '<script>alert(1)</script>']);
    $this->get(route('members.show', $member))->assertOk()
        ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('requires company details when changing a member to Corporate', function () {
    $member = Member::factory()->create();
    $corporate = MembershipCategory::where('name', 'Corporate')->firstOrFail();
    $this->actingAs(User::factory()->administrator()->create())
        ->put(route('members.update', $member), memberPayload(['membership_category_id' => $corporate->id]))
        ->assertSessionHasErrors('company_name');

    $this->put(route('members.update', $member), memberPayload([
        'membership_category_id' => $corporate->id,
        'company_name' => 'Harbor Services',
    ]))->assertSessionHasNoErrors();
    expect($member->fresh()->company_name)->toBe('Harbor Services');
});

it('clears company details when changing away from Corporate', function () {
    $member = Member::factory()->create([
        'membership_category_id' => MembershipCategory::where('name', 'Corporate')->firstOrFail()->id,
        'company_name' => 'Former Company',
    ]);
    $this->actingAs(User::factory()->administrator()->create())
        ->put(route('members.update', $member), memberPayload(['company_name' => 'Stale input']))
        ->assertSessionHasNoErrors();

    expect($member->fresh()->company_name)->toBeNull();
    $this->get(route('members.show', $member))->assertOk()->assertDontSee('Company name');
});

it('ignores company input for non-Corporate registrations', function () {
    $this->actingAs(User::factory()->administrator()->create())
        ->post(route('members.store'), memberPayload(['company_name' => 'Not applicable']))
        ->assertSessionHasNoErrors();
    expect(Member::firstOrFail()->company_name)->toBeNull();
});

it('offers registration for an empty directory and clearing for unmatched filters', function () {
    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index'))->assertOk()
        ->assertSee('Register your first member')->assertDontSee('Clear filters');

    $this->get(route('members.index', ['search' => 'No match']))->assertOk()
        ->assertSee('Clear filters')->assertDontSee('Register your first member');
});

it('identifies the member in deactivation confirmation and labels account status', function () {
    $member = Member::factory()->create(['first_name' => 'Jamie', 'last_name' => 'Soriano']);

    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.show', $member))->assertOk()
        ->assertSee('Account status')->assertSee('Deactivate <strong>Jamie Soriano</strong>', false)
        ->assertSee('('.$member->membership_number.')?');
});

it('shows a validation summary while preserving submitted member details', function () {
    $this->actingAs(User::factory()->administrator()->create())
        ->followingRedirects()
        ->from(route('members.create'))
        ->post(route('members.store'), memberPayload(['email' => 'invalid']))
        ->assertOk()
        ->assertSee('Member details could not be saved.')
        ->assertSee('value="Jamie"', false)->assertSee('value="invalid"', false);
});

it('stores and displays the peso fees for membership levels', function () {
    $this->actingAs(User::factory()->administrator()->create());
    $form = $this->get(route('members.create'))->assertOk()
        ->assertSee('Membership Levels')->assertSee('js/member-form.js');

    foreach (['Basic' => '200.00', 'Silver' => '400.00', 'Gold' => '600.00', 'Platinum' => '1000.00'] as $name => $fee) {
        $level = MembershipLevel::where('name', $name)->firstOrFail();
        expect($level->fee)->toBe($fee);
        $form->assertSee($name.' - &#8369;'.number_format((float) $fee, 2), false);

        $member = Member::factory()->for($level, 'level')->create();
        $this->get(route('members.show', $member))->assertOk()
            ->assertSee('Membership fee')->assertSee('&#8369;'.number_format((float) $fee, 2), false);
        $this->get(route('members.edit', $member))->assertOk()
            ->assertSee($name.' - &#8369;'.number_format((float) $fee, 2), false);
    }
});

function memberPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Jamie',
        'last_name' => 'Soriano',
        'email' => 'jamie@example.com',
        'phone' => '09123456789',
        'address' => 'Sample address',
        'membership_category_id' => MembershipCategory::where('name', 'Student')->firstOrFail()->id,
        'membership_level_id' => MembershipLevel::where('name', 'Basic')->firstOrFail()->id,
    ], $overrides);
}

it('redirects guests away from every member operation', function () {
    $member = Member::factory()->create();

    foreach ([
        ['get', route('members.index')],
        ['get', route('members.create')],
        ['get', route('members.show', $member)],
        ['get', route('members.edit', $member)],
        ['post', route('members.store')],
        ['put', route('members.update', $member)],
        ['delete', route('members.destroy', $member)],
        ['patch', route('members.activate', $member)],
    ] as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }

    expect(Member::count())->toBe(1);
    expect($member->fresh()->is_active)->toBeTrue();
});

it('forbids non-administrators from reading or changing members', function () {
    $member = Member::factory()->create();
    $this->actingAs(User::factory()->create());

    foreach ([
        ['get', route('members.index')],
        ['get', route('members.create')],
        ['get', route('members.show', $member)],
        ['get', route('members.edit', $member)],
        ['post', route('members.store')],
        ['put', route('members.update', $member)],
        ['delete', route('members.destroy', $member)],
        ['patch', route('members.activate', $member)],
    ] as [$method, $url]) {
        $this->{$method}($url)->assertForbidden();
    }
});

it('renders the member list and registration choices for staff', function () {
    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index'))->assertOk()->assertSee('No members found.');

    $this->get(route('members.create'))->assertOk()
        ->assertSee('Student')->assertSee('Regular')->assertSee('Senior Citizen')
        ->assertSee('Corporate')->assertSee('Basic')->assertSee('Silver')
        ->assertSee('Gold')->assertSee('Platinum');
});

it('registers active members with unique server-generated numbers', function () {
    $this->travelTo(Carbon::create(2026, 10, 28, 12));
    $this->actingAs(User::factory()->administrator()->create());

    $this->post(route('members.store'), memberPayload([
        'email' => 'JAMIE@EXAMPLE.COM',
        'membership_number' => 'FORGED',
        'is_active' => false,
    ]))->assertSessionHasNoErrors()->assertRedirect();

    $first = Member::firstOrFail();
    expect($first->email)->toBe('jamie@example.com');
    expect($first->membership_number)->toStartWith('MEM-')->not->toBe('FORGED');
    expect($first->membership_number)->toBe('MEM-2026-000001');
    expect($first->is_active)->toBeTrue();

    $this->post(route('members.store'), memberPayload(['email' => 'another@example.com']))
        ->assertSessionHasNoErrors()->assertRedirect();

    expect(Member::count())->toBe(2);
    expect(Member::pluck('membership_number')->unique()->count())->toBe(2);
    expect(Member::latest('id')->first()->membership_number)->toBe('MEM-2026-000002');
    expect($first->category->name)->toBe('Student');
    expect($first->level->name)->toBe('Basic');
});

it('rejects invalid member inputs without creating a record', function (array $override, string $field) {
    $this->actingAs(User::factory()->administrator()->create())
        ->from(route('members.create'))
        ->post(route('members.store'), memberPayload($override))
        ->assertRedirect(route('members.create'))->assertSessionHasErrors($field);

    expect(Member::count())->toBe(0);
})->with([
    'missing first name' => [['first_name' => ''], 'first_name'],
    'missing last name' => [['last_name' => ''], 'last_name'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'missing email' => [['email' => ''], 'email'],
    'long first name' => [['first_name' => str_repeat('x', 101)], 'first_name'],
    'long last name' => [['last_name' => str_repeat('x', 101)], 'last_name'],
    'long phone' => [['phone' => str_repeat('1', 31)], 'phone'],
    'long address' => [['address' => str_repeat('x', 1001)], 'address'],
    'missing category' => [['membership_category_id' => ''], 'membership_category_id'],
    'nonexistent category' => [['membership_category_id' => 99999], 'membership_category_id'],
    'invalid category' => [['membership_category_id' => 'Student'], 'membership_category_id'],
    'missing level' => [['membership_level_id' => ''], 'membership_level_id'],
    'nonexistent level' => [['membership_level_id' => 99999], 'membership_level_id'],
    'invalid level' => [['membership_level_id' => 'Basic'], 'membership_level_id'],
]);

it('rejects duplicate emails on create and update', function () {
    $existing = Member::factory()->create(['email' => 'existing@example.com']);
    $other = Member::factory()->create(['email' => 'other@example.com']);
    $this->actingAs(User::factory()->administrator()->create());

    $this->post(route('members.store'), memberPayload(['email' => 'EXISTING@example.com']))
        ->assertSessionHasErrors('email');

    $this->put(route('members.update', $other), memberPayload(['email' => $existing->email]))
        ->assertSessionHasErrors('email');

    expect(Member::count())->toBe(2);
    expect($other->fresh()->email)->toBe('other@example.com');
});

it('updates details without changing the membership number or status', function () {
    $member = Member::factory()->inactive()->create();
    $number = $member->membership_number;

    $this->actingAs(User::factory()->administrator()->create())
        ->put(route('members.update', $member), memberPayload([
            'first_name' => 'Updated',
            'membership_number' => 'FORGED',
            'is_active' => true,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('members.show', $member));

    expect($member->fresh()->first_name)->toBe('Updated');
    expect($member->fresh()->membership_number)->toBe($number);
    expect($member->fresh()->is_active)->toBeFalse();

    $this->put(route('members.update', $member), memberPayload(['first_name' => 'Updated again']))
        ->assertSessionHasNoErrors();
    $this->get(route('members.edit', $member))->assertOk()->assertSee('Updated again');
});

it('deactivates without deleting and can reactivate the same profile', function () {
    $member = Member::factory()->create();
    $number = $member->membership_number;
    $this->actingAs(User::factory()->administrator()->create());

    $this->delete(route('members.destroy', $member))->assertRedirect(route('members.show', $member));
    $this->delete(route('members.destroy', $member))->assertRedirect(route('members.show', $member));
    expect(Member::count())->toBe(1);
    expect($member->fresh()->is_active)->toBeFalse();
    $this->get(route('members.show', $member))->assertOk()->assertSee('Inactive')->assertSee('Reactivate Member');

    $this->patch(route('members.activate', $member))->assertRedirect(route('members.show', $member));
    expect($member->fresh()->is_active)->toBeTrue();
    expect($member->fresh()->membership_number)->toBe($number);
});

it('combines name category level and status filters', function () {
    $category = MembershipCategory::where('name', 'Student')->firstOrFail();
    $level = MembershipLevel::where('name', 'Gold')->firstOrFail();
    Member::factory()->for($category, 'category')->for($level, 'level')->create([
        'first_name' => 'Jamie', 'last_name' => 'Soriano', 'email' => 'matching@example.com',
    ]);
    Member::factory()->for($category, 'category')->for($level, 'level')->inactive()->create([
        'first_name' => 'Jamie', 'last_name' => 'Soriano', 'email' => 'inactive@example.com',
    ]);
    Member::factory()->create(['first_name' => 'Different', 'email' => 'different@example.com']);

    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index', [
            'search' => 'Jamie Soriano', 'category' => $category->id, 'level' => $level->id, 'status' => 'active',
        ]))->assertOk()->assertSee('matching@example.com')
        ->assertDontSee('inactive@example.com')->assertDontSee('different@example.com');
});

it('filters independently by category level or inactive status', function (string $filter) {
    $category = MembershipCategory::where('name', 'Student')->firstOrFail();
    $level = MembershipLevel::where('name', 'Gold')->firstOrFail();
    Member::factory()->for($category, 'category')->for($level, 'level')->inactive()->create(['email' => 'match@example.com']);
    Member::factory()->create(['email' => 'exclude@example.com']);
    $value = match ($filter) {
        'category' => $category->id,
        'level' => $level->id,
        'status' => 'inactive',
    };

    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index', [$filter => $value]))
        ->assertOk()->assertSee('match@example.com')->assertDontSee('exclude@example.com');
})->with(['category', 'level', 'status']);

it('rejects invalid search filters', function () {
    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index', ['category' => 9999, 'level' => 'wrong', 'status' => 'unknown']))
        ->assertSessionHasErrors(['category', 'level', 'status']);
});

it('paginates members while keeping the filters', function () {
    Member::factory()->count(11)->create(['first_name' => 'Pagination']);
    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.index', ['search' => 'Pagination']))
        ->assertOk()->assertSee('Page 1 of 2')->assertSee('search=Pagination', false);

    $this->get(route('members.index', ['search' => 'Pagination', 'page' => 2]))
        ->assertOk()->assertSee('Page 2 of 2');
});

it('escapes member details and treats search text as data', function () {
    $member = Member::factory()->create([
        'first_name' => '<script>alert(1)</script>',
        'address' => '<img src=x onerror=alert(1)>',
    ]);
    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.show', $member))
        ->assertOk()->assertSee('&lt;script&gt;', false)->assertSee('&lt;img', false)
        ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x', false);

    $this->get(route('members.index', ['search' => "' OR 1=1 --"]))
        ->assertOk()->assertSee('No members found.');
});

it('returns not found for unknown member profiles', function () {
    $this->actingAs(User::factory()->administrator()->create())
        ->get(route('members.show', 99999))->assertNotFound();
});

it('does not expose the retired tasks exercise', function () {
    $this->get('/tasks')->assertNotFound();
    $this->post('/tasks')->assertNotFound();
    $this->get('/tasks/1')->assertNotFound();
});

it('shortens legacy numbers without changing details and is safe to rerun', function () {
    $this->travelTo(Carbon::create(2026, 10, 28, 12));
    $member = Member::factory()->create(['email' => 'preserved@example.com']);
    DB::table('members')->where('id', $member->id)->update([
        'membership_number' => 'MEM-12345678-1234-1234-1234-123456789ABC',
    ]);

    $migration = require database_path('migrations/2026_10_28_022706_shorten_member_numbers.php');
    $migration->up();
    $migration->up();

    expect($member->fresh()->membership_number)->toBe('MEM-2026-000001');
    expect($member->fresh()->email)->toBe('preserved@example.com');
    expect(Member::count())->toBe(1);
});
