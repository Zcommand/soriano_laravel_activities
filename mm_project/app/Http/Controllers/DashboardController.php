<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        MembershipTransaction::expireDue();

        return view('dashboard', [
            'summary' => [
                'members' => Member::count(), 'active' => Member::where('is_active', true)->count(),
                'pending' => MembershipTransaction::where('status', 'Pending')->count(),
                'paid' => MembershipTransaction::where('status', 'Paid')->count(),
                'collected' => Payment::whereNull('voided_at')->sum('amount'),
            ],
            'attention' => MembershipTransaction::with('payments')->whereIn('status', ['Paid', 'Pending'])->orderBy('status')->latest('id')->limit(5)->get(),
            'transactions' => MembershipTransaction::with('payments')->latest('id')->limit(5)->get(),
        ]);
    }
}
