@if($records->hasPages())
<nav class="settings-pagination" aria-label="Pagination">
    @if($records->previousPageUrl())<a class="button secondary-button" href="{{ $records->previousPageUrl() }}">Previous</a>@endif
    <span>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
    @if($records->nextPageUrl())<a class="button secondary-button" href="{{ $records->nextPageUrl() }}">Next</a>@endif
</nav>
@endif
