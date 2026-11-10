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
                'collected' => Payment::sum('amount'),
            ],
            'transactions' => MembershipTransaction::with('payments')->latest('id')->limit(5)->get(),
        ]);
    }
}
