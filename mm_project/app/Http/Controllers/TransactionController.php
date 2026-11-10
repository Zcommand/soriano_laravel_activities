<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Services\MembershipWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        MembershipTransaction::expireDue();
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(MembershipTransaction::STATUSES)]]);
        $transactions = MembershipTransaction::with('payments')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $query->whereLike('reference', '%'.$search.'%')->orWhereLike('member_name', '%'.$search.'%')->orWhereLike('membership_number', '%'.$search.'%');
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')->paginate(12)->withQueryString();

        return view('transactions.index', ['transactions' => $transactions, 'statuses' => MembershipTransaction::STATUSES]);
    }

    public function create(Request $request): View
    {
        $data = $request->validate(['member_id' => ['nullable', 'integer', 'exists:members,id'], 'type' => ['nullable', Rule::in(['Application', 'Renewal'])]]);

        return view('transactions.create', [
            'members' => Member::with(['level', 'currentMembership'])->where('is_active', true)->orderBy('last_name')->orderBy('id')->get(),
            'levels' => MembershipLevel::orderBy('id')->get(),
            'selectedMember' => isset($data['member_id']) ? Member::find($data['member_id']) : null,
            'selectedType' => $data['type'] ?? 'Application',
        ]);
    }

    public function store(TransactionRequest $request, MembershipWorkflow $workflow): RedirectResponse
    {
        $transaction = $workflow->create($request->validated(), $request->user());

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaction saved. Record payment, then approve the membership.');
    }

    public function show(MembershipTransaction $transaction): View
    {
        MembershipTransaction::expireDue();

        return view('transactions.show', ['transaction' => $transaction->refresh()->load(['member', 'payments.recorder', 'creator', 'approver'])]);
    }

    public function approve(Request $request, MembershipTransaction $transaction, MembershipWorkflow $workflow): RedirectResponse
    {
        $workflow->approve($transaction, $request->user());

        return redirect()->route('transactions.show', $transaction)->with('success', 'Membership approved. Its validity dates have been recorded.');
    }

    public function cancel(Request $request, MembershipTransaction $transaction, MembershipWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:1000']]);
        $workflow->cancel($transaction, $data['cancellation_reason']);

        return redirect()->route('transactions.show', $transaction)->with('success', 'Transaction cancelled. Its history has been preserved.');
    }

    public function history(Member $member): View
    {
        MembershipTransaction::expireDue();

        return view('transactions.history', ['member' => $member, 'transactions' => $member->transactions()->with('payments')->latest('id')->paginate(12)]);
    }
}
