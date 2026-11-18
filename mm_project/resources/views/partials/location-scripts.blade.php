<script src="{{ asset('js/location-weather.js') }}?v=1"></script>
@if(config('services.google_maps.key'))
    <script async src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google_maps.key')) }}&amp;loading=async&amp;libraries=marker&amp;callback=initMembershipMap"></script>
@endif
