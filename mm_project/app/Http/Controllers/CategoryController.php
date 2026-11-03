<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\MembershipCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('membership-settings.index', [
            'kind' => 'categories', 'label' => 'Category',
            'records' => MembershipCategory::withCount('members')->orderBy('id')->paginate(12),
        ]);
    }

    public function create(): View
    {
        return view('membership-settings.form', ['kind' => 'categories', 'label' => 'Category', 'record' => new MembershipCategory]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        MembershipCategory::create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category added.');
    }

    public function edit(MembershipCategory $category): View
    {
        return view('membership-settings.form', ['kind' => 'categories', 'label' => 'Category', 'record' => $category]);
    }

    public function update(CategoryRequest $request, MembershipCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(MembershipCategory $category): RedirectResponse
    {
        if ($category->members()->exists()) {
            return back()->withErrors(['delete' => 'This category is assigned to members. Reassign them before deleting it.']);
        }

        try {
            $category->delete();
        } catch (QueryException $exception) {
            if (! in_array($exception->getCode(), ['23000', '23503'], true)) {
                throw $exception;
            }

            return back()->withErrors(['delete' => 'This category is in use and cannot be deleted.']);
        }

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
