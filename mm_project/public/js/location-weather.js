(() => {
    const panel = document.querySelector('[data-location-panel]');
    if (!panel) return;

    const fixed = panel.hasAttribute('data-fixed-location');
    const latitude = panel.querySelector('#latitude');
    const longitude = panel.querySelector('#longitude');
    const summary = panel.querySelector('[data-weather-summary]');
    const detail = panel.querySelector('[data-weather-detail]');
    const observed = panel.querySelector('[data-weather-time]');
    const status = panel.querySelector('[data-map-status]');
    let map;
    let marker;
    let pending;
    let timer;
    let weatherVersion = 0;

    function coordinates() {
        if (fixed) return {lat: Number(panel.dataset.latitude), lng: Number(panel.dataset.longitude)};
        if (latitude.value === '' || longitude.value === '' || !latitude.validity.valid || !longitude.validity.valid) return null;
        return {lat: Number(latitude.value), lng: Number(longitude.value)};
    }

    async function loadWeather(position) {
        const version = ++weatherVersion;
        pending?.abort();
        pending = new AbortController();
        detail.textContent = '';
        observed.textContent = '';
        if (!position) {
            summary.textContent = 'Enter valid latitude and longitude to load weather.';
            return;
        }
        summary.textContent = 'Loading weather…';
        const url = new URL(panel.dataset.weatherUrl, window.location.origin);
        url.searchParams.set('lat', position.lat);
        url.searchParams.set('lon', position.lng);
        try {
            const response = await fetch(url, {headers: {Accept: 'application/json'}, signal: pending.signal});
            if (!response.ok) throw new Error('Weather unavailable');
            const weather = await response.json();
            if (version !== weatherVersion) return;
            summary.textContent = `${fixed ? '' : weather.place + ': '}${Number(weather.temperature).toFixed(1)}°C${weather.description ? ' · ' + weather.description : ''}`;
            const parts = [];
            if (weather.humidity !== null) parts.push(`Humidity ${weather.humidity}%`);
            if (weather.wind_speed !== null) parts.push(`Wind ${(Number(weather.wind_speed) * 3.6).toFixed(1)} km/h`);
            detail.textContent = parts.join(' · ');
            if (weather.observed_at) observed.textContent = `Observed ${new Date(weather.observed_at * 1000).toLocaleString()}`;
        } catch (error) {
            if (error.name !== 'AbortError' && version === weatherVersion) summary.textContent = fixed ? 'Weather is currently unavailable.' : 'Weather is currently unavailable. You can still save this application.';
        }
    }

    function updatePosition(fromMap = false) {
        const position = coordinates();
        if (position) {
            status.textContent = `${fixed ? 'Office:' : 'Selected:'} ${position.lat.toFixed(5)}, ${position.lng.toFixed(5)}`;
            panel.querySelector('[data-maps-link]').href = `https://www.google.com/maps/search/?api=1&query=${position.lat},${position.lng}`;
            if (marker) marker.position = position;
            if (map && !fromMap) map.panTo(position);
        }
        loadWeather(position);
    }

    window.initMembershipMap = () => {
        const position = coordinates() || {lat: 14.5995, lng: 120.9842};
        try {
            map = new google.maps.Map(panel.querySelector('[data-location-map]'), {
                center: position, zoom: 13, mapId: 'DEMO_MAP_ID',
                streetViewControl: false, mapTypeControl: false, fullscreenControl: !fixed, gestureHandling: 'cooperative',
            });
            marker = new google.maps.marker.AdvancedMarkerElement({map, position, title: fixed ? 'Company location' : 'Selected application location'});
            if (!fixed) map.addListener('click', (event) => {
                latitude.value = event.latLng.lat().toFixed(7);
                longitude.value = event.latLng.lng().toFixed(7);
                updatePosition(true);
            });
        } catch {
            status.textContent = fixed ? 'The map could not load. Use the Google Maps link below.' : 'The map could not load. Enter coordinates below instead.';
        }
    };
    window.gm_authFailure = () => {
        status.textContent = fixed ? 'Map unavailable. Use the Google Maps link below.' : 'Google Maps could not authenticate. Check the browser key, billing, and website restrictions. You can still enter coordinates.';
    };
    [latitude, longitude].filter(Boolean).forEach((field) => field.addEventListener('input', () => {
        clearTimeout(timer);
        ++weatherVersion;
        pending?.abort();
        timer = setTimeout(() => updatePosition(), 500);
    }));
    updatePosition();
})();
