<?php

namespace App\Http\Controllers;

use App\Http\Requests\LevelRequest;
use App\Models\MembershipLevel;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function index(): View
    {
        return view('membership-settings.index', [
            'kind' => 'levels', 'label' => 'Level',
            'records' => MembershipLevel::withCount('members')->orderBy('id')->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('membership-settings.form', ['kind' => 'levels', 'label' => 'Level', 'record' => new MembershipLevel]);
    }

    public function store(LevelRequest $request): RedirectResponse
    {
        MembershipLevel::create($request->validated());

        return redirect()->route('levels.index')->with('success', 'Level added.');
    }

    public function edit(MembershipLevel $level): View
    {
        return view('membership-settings.form', ['kind' => 'levels', 'label' => 'Level', 'record' => $level]);
    }

    public function update(LevelRequest $request, MembershipLevel $level): RedirectResponse
    {
        $level->update($request->validated());

        return redirect()->route('levels.index')->with('success', 'Level updated.');
    }

    public function destroy(MembershipLevel $level): RedirectResponse
    {
        if ($level->members()->exists()) {
            return back()->withErrors(['delete' => 'This level is assigned to members. Reassign them before deleting it.']);
        }

        try {
            $level->delete();
        } catch (QueryException $exception) {
            if (! in_array($exception->getCode(), ['23000', '23503'], true)) {
                throw $exception;
            }

            return back()->withErrors(['delete' => 'This level is in use and cannot be deleted.']);
        }

        return redirect()->route('levels.index')->with('success', 'Level deleted.');
    }
}
