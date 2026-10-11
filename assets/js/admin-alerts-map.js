(function(){
    'use strict';

    const mapElement = document.getElementById('alertsMap');
    const alerts = Array.isArray(window.ADMIN_ALERTS_MAP_DATA) ? window.ADMIN_ALERTS_MAP_DATA : [];

    if (!mapElement || typeof L === 'undefined') return;

    const map = L.map(mapElement, {
        zoomControl: true,
        scrollWheelZoom: true,
        preferCanvas: true
    }).setView([23.8859, 45.0792], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: 'Leaflet | © OpenStreetMap'
    }).addTo(map);

    const markers = new Map();
    const bounds = L.latLngBounds([]);
    let hasBounds = false;

    function isValidPoint(alert){
        const lat = Number(alert.lat);
        const lon = Number(alert.lon);
        return Number.isFinite(lat) && Number.isFinite(lon);
    }

    function escapeHtml(value){
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function markerIcon(severity){
        const safeSeverity = ['critical', 'warning', 'info', 'success'].includes(severity) ? severity : 'info';
        return L.divIcon({
            className: 'alerts-marker-wrapper',
            html: `<div class="alerts-marker ${safeSeverity}"></div>`,
            iconSize: [26, 26],
            iconAnchor: [13, 26],
            popupAnchor: [0, -25]
        });
    }

    function popupContent(alert){
        return `
            <div class="alert-popup-title">${escapeHtml(alert.title)}</div>
            <div class="alert-popup-meta">
                المنطقة: <b>${escapeHtml(alert.region)}</b><br>
                المؤشر: <b>${escapeHtml(alert.value)}</b><br>
                الدرجة: <b>${escapeHtml(alert.score)}%</b>
            </div>
        `;
    }

    function setActiveCard(index, scrollIntoView){
        document.querySelectorAll('.alert-map-card').forEach(card => card.classList.remove('active'));
        const card = document.querySelector(`.alert-map-card[data-alert-index="${index}"]`);
        if (!card) return;
        card.classList.add('active');
        if (scrollIntoView) {
            card.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    }

    function focusAlert(index, shouldScroll){
        const alert = alerts[index];
        if (!alert || !isValidPoint(alert)) return;

        const lat = Number(alert.lat);
        const lon = Number(alert.lon);
        const marker = markers.get(Number(index));

        map.setView([lat, lon], Math.max(map.getZoom(), 10), {animate: true});

        if (marker) {
            marker.openPopup();
        }

        setActiveCard(index, Boolean(shouldScroll));
    }

    alerts.forEach((alert, index) => {
        if (!isValidPoint(alert)) return;

        const lat = Number(alert.lat);
        const lon = Number(alert.lon);
        const marker = L.marker([lat, lon], {icon: markerIcon(alert.severity)})
            .bindPopup(popupContent(alert))
            .addTo(map);

        marker.on('click', () => focusAlert(index, true));
        markers.set(index, marker);
        bounds.extend([lat, lon]);
        hasBounds = true;
    });

    if (hasBounds && bounds.isValid()) {
        map.fitBounds(bounds, {padding: [45, 45], maxZoom: 7});
    }

    document.querySelectorAll('.alert-map-card').forEach(card => {
        card.addEventListener('click', () => {
            const index = Number(card.dataset.alertIndex);
            focusAlert(index, false);
        });
    });

    setTimeout(() => map.invalidateSize(), 250);
})();
