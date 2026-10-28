<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:membership_categories,id'],
            'level' => ['nullable', 'integer', 'exists:membership_levels,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $members = Member::with(['category', 'level'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('first_name', '%'.$search.'%')
                        ->orWhereLike('last_name', '%'.$search.'%')
                        ->orWhereLike('email', '%'.$search.'%')
                        ->orWhereLike('membership_number', '%'.$search.'%')
                        ->orWhere(function (Builder $names) use ($search): void {
                            foreach (preg_split('/\s+/', trim($search)) as $part) {
                                $names->where(function (Builder $name) use ($part): void {
                                    $name->whereLike('first_name', '%'.$part.'%')
                                        ->orWhereLike('last_name', '%'.$part.'%');
                                });
                            }
                        });
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $query, $id) => $query->where('membership_category_id', $id))
            ->when($filters['level'] ?? null, fn (Builder $query, $id) => $query->where('membership_level_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('is_active', $status === 'active'))
            ->latest('id')->paginate(10)->withQueryString();

        return view('members.index', [
            'members' => $members,
            'categories' => MembershipCategory::orderBy('id')->get(),
            'levels' => MembershipLevel::orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('members.form', [
            'member' => new Member,
            'categories' => MembershipCategory::orderBy('id')->get(),
            'levels' => MembershipLevel::orderBy('id')->get(),
        ]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = DB::transaction(fn (): Member => Member::create($request->validated()));

        return redirect()->route('members.show', $member)->with('success', 'Member registered successfully.');
    }

    public function show(Member $member): View
    {
        return view('members.show', ['member' => $member->load(['category', 'level'])]);
    }

    public function edit(Member $member): View
    {
        return view('members.form', [
            'member' => $member,
            'categories' => MembershipCategory::orderBy('id')->get(),
            'levels' => MembershipLevel::orderBy('id')->get(),
        ]);
    }

    public function update(MemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->validated());

        return redirect()->route('members.show', $member)->with('success', 'Member details updated.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->is_active = false;
        $member->save();

        return redirect()->route('members.show', $member)->with('success', 'Member deactivated. Their record has been preserved.');
    }

    public function activate(Member $member): RedirectResponse
    {
        $member->is_active = true;
        $member->save();

        return redirect()->route('members.show', $member)->with('success', 'Member reactivated.');
    }
}
