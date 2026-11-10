@php($membership = $contextMember->currentMembership)
<h2>Current membership</h2>
<dl class="membership-context-grid">
    <div><dt>{{ $membership ? 'Membership level' : 'Assigned level' }}</dt><dd>{{ $membership?->level_name ?? $contextMember->level->name }}</dd></div>
    <div><dt>Membership status</dt><dd>{{ $membership ? ($membership->displayStatus() === 'Expired' ? 'Expired' : 'Active') : 'Not yet approved' }}</dd></div>
    <div><dt>Valid from</dt><dd>{{ $membership?->starts_at?->format('M j, Y') ?? 'Not started' }}</dd></div>
    <div><dt>Valid until</dt><dd>{{ $membership?->expires_at?->format('M j, Y') ?? 'Not started' }}</dd></div>
</dl>
@unless($membership)<p class="muted">An assigned level becomes an active membership only after payment and approval.</p>@endunless
