<aside class="company-location" aria-labelledby="company-location-heading" data-location-panel data-fixed-location data-latitude="{{ config('services.google_maps.office_latitude') }}" data-longitude="{{ config('services.google_maps.office_longitude') }}" data-weather-url="{{ route('weather.show') }}">
    <header class="desk-section-heading"><h2 id="company-location-heading">Company location</h2></header>
    <h3>Main office</h3>
    <p class="muted">{{ config('services.google_maps.office_location') }}</p>
    <section class="company-weather" aria-label="Weather at company location" aria-live="polite" aria-atomic="true">
        <p data-weather-summary>Loading weather…</p>
        <p class="muted" data-weather-detail></p>
        <p class="muted" data-weather-time></p>
    </section>
    <div class="company-map" data-location-map aria-label="Company location map"><p class="map-placeholder">{{ config('services.google_maps.key') ? 'Loading Google Maps…' : 'Map unavailable. Use the link below to view the office location.' }}</p></div>
    <p class="muted company-map-status" data-map-status role="status"></p>
    <a class="company-map-link" data-maps-link href="https://www.google.com/maps/search/?api=1&amp;query={{ config('services.google_maps.office_latitude') }},{{ config('services.google_maps.office_longitude') }}" target="_blank" rel="noopener noreferrer">Open in Google Maps ↗</a>
    <p class="location-credit muted">Weather: <a href="https://openweathermap.org/" target="_blank" rel="noopener noreferrer">OpenWeather</a> · Cached for up to 10 minutes.</p>
</aside>
