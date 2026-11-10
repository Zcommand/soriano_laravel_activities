<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Services\MembershipWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('payments.index', ['payments' => Payment::with(['transaction', 'recorder'])->latest('id')->paginate(15)]);
    }

    public function create(MembershipTransaction $transaction): View
    {
        $transaction->load(['payments', 'member']);
        abort_unless($transaction->status === 'Pending' && $transaction->member->is_active, 422, 'Only Pending transactions for active members can receive payment.');

        return view('payments.create', ['transaction' => $transaction, 'methods' => Payment::METHODS]);
    }

    public function store(PaymentRequest $request, MembershipTransaction $transaction, MembershipWorkflow $workflow): RedirectResponse
    {
        $payment = $workflow->pay($transaction, $request->validated(), $request->user());

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded. This is a simulation; no money was charged.');
    }

    public function show(Payment $payment): View
    {
        return view('payments.show', ['payment' => $payment->load(['transaction.payments', 'recorder'])]);
    }
}
