<fieldset class="application-location" data-location-panel data-weather-url="{{ route('weather.show') }}">
    <legend>Application location</legend>
    <p class="muted">Click the map to choose the applicant’s location, or enter coordinates below. Manila is the starting location.</p>
    <div class="application-map" id="application-map" data-location-map aria-label="Applicant location map">
        <p class="map-placeholder">{{ config('services.google_maps.key') ? 'Loading Google Maps…' : 'Google Maps is not configured yet. You can enter coordinates below.' }}</p>
    </div>
    <p class="muted" data-map-status role="status">The selected location will be saved with this transaction.</p>
    <div class="form-grid coordinate-fields">
        <div class="field"><label for="latitude">Latitude</label><input id="latitude" name="latitude" type="number" min="-90" max="90" step="0.0000001" value="{{ old('latitude', '14.5995') }}" inputmode="decimal"></div>
        <div class="field"><label for="longitude">Longitude</label><input id="longitude" name="longitude" type="number" min="-180" max="180" step="0.0000001" value="{{ old('longitude', '120.9842') }}" inputmode="decimal"></div>
    </div>
    <section class="location-weather" aria-label="Weather at selected location" aria-live="polite" aria-atomic="true">
        <div><h2>Current weather</h2><p data-weather-summary>Loading weather…</p><p class="muted" data-weather-detail></p><p class="muted" data-weather-time></p></div>
        <a data-maps-link href="https://www.google.com/maps/search/?api=1&amp;query=14.5995,120.9842" target="_blank" rel="noopener noreferrer">Open in Google Maps ↗</a>
    </section>
    <p class="location-credit muted">Weather data: <a href="https://openweathermap.org/" target="_blank" rel="noopener noreferrer">OpenWeather</a> · Results may be cached for up to 10 minutes.</p>
</fieldset>
