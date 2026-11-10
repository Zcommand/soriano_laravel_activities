<ol class="ledger-list" aria-label="Membership transactions">
@forelse($transactions as $transaction)
    <li class="ledger-row">
        <div><a class="ledger-reference" href="{{ route('transactions.show', $transaction) }}">{{ $transaction->reference }}</a><p class="muted">{{ $transaction->type }} · {{ $transaction->created_at->format('M j, Y') }}</p></div>
        <div><strong>{{ $transaction->member_name }}</strong><p class="muted">{{ $transaction->level_name }} · {{ $transaction->membership_number }}</p></div>
        <div><span class="muted">Fee</span><p>₱{{ number_format($transaction->fee, 2) }}</p></div>
        <div><span class="status transaction-status status-{{ strtolower($transaction->displayStatus()) }}">{{ $transaction->displayStatus() }}</span></div>
        <a class="button secondary-button" href="{{ route('transactions.show', $transaction) }}" aria-label="View {{ $transaction->reference }}">View</a>
    </li>
@empty
    <li class="empty-message">No transactions found. Create a membership application to begin.</li>
@endforelse
</ol>
