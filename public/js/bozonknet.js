document.addEventListener('DOMContentLoaded', () => {
    const dataElement = document.getElementById('map-data');
    const mapElement = document.getElementById('map');

    if (!dataElement || !mapElement || typeof L === 'undefined') {
        return;
    }

    const config = JSON.parse(dataElement.textContent);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const center = [Number(config.coverage.latitude), Number(config.coverage.longitude)];
    const villageSelect = document.getElementById('village-select');
    const resultBox = document.getElementById('coverage-result');
    const resultBadge = document.getElementById('result-badge');
    const resultTitle = document.getElementById('result-title');
    const resultDescription = document.getElementById('result-description');
    const resultDistance = document.getElementById('result-distance');
    const subscribeButton = document.getElementById('subscribe-button');
    const requestToggle = document.getElementById('request-toggle');
    const requestForm = document.getElementById('area-request-form');
    const requestMessage = document.getElementById('request-message');
    const statusMeta = {
        available: { className: 'ok', color: '#16a36a' },
        expansion: { className: 'soon', color: '#e99511' },
        unavailable: { className: 'no', color: '#df5368' },
    };

    const map = L.map('map', { scrollWheelZoom: false }).setView(center, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    L.circle(center, {
        radius: Number(config.coverage.expansionRadius),
        color: '#e99511',
        weight: 2,
        dashArray: '7,7',
        fillColor: '#e99511',
        fillOpacity: .04,
    }).addTo(map).bindPopup('Area perluasan jaringan');

    L.circle(center, {
        radius: Number(config.coverage.availableRadius),
        color: '#16a36a',
        weight: 2,
        fillColor: '#16a36a',
        fillOpacity: .12,
    }).addTo(map).bindPopup('Area layanan tersedia');

    const markerIcon = (color, size = 14) => L.divIcon({
        className: '',
        html: `<span style="display:block;width:${size}px;height:${size}px;border-radius:50%;background:${color};border:3px solid white;box-shadow:0 3px 10px rgba(13,44,70,.35)"></span>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size / 2],
    });

    config.villages.forEach((village) => {
        const meta = statusMeta[village.status] ?? statusMeta.unavailable;
        L.marker([Number(village.latitude), Number(village.longitude)], { icon: markerIcon(meta.color) })
            .addTo(map)
            .bindPopup(`<div class="map-popup"><strong>${escapeHtml(village.name)}</strong><br><small>${statusLabel(village.status)}</small></div>`);
    });

    config.odps.forEach((odp) => {
        const availablePorts = Math.max(0, Number(odp.totalPorts) - Number(odp.usedPorts));
        const color = odp.status === 'planned' ? '#e99511' : '#0878d1';
        L.marker([Number(odp.latitude), Number(odp.longitude)], { icon: markerIcon(color, 17) })
            .addTo(map)
            .bindPopup(`<div class="map-popup"><strong>${escapeHtml(odp.code)}</strong><br>${escapeHtml(odp.name)}<br><small>${odp.status === 'planned' ? 'Rencana pembangunan' : `${availablePorts} port tersedia`}</small></div>`);
    });

    let selectedMarker = null;

    map.on('click', async (event) => {
        const latitude = event.latlng.lat;
        const longitude = event.latlng.lng;
        setSelectedLocation(latitude, longitude, `Koordinat ${latitude.toFixed(5)}, ${longitude.toFixed(5)}`);
        await checkCoverage({ latitude, longitude }, event.latlng);
    });

    document.getElementById('check-village').addEventListener('click', () => checkSelectedVillage());
    villageSelect.addEventListener('change', () => {
        if (villageSelect.value) {
            checkSelectedVillage();
        }
    });

    document.querySelectorAll('[data-village-id]').forEach((button) => {
        button.addEventListener('click', () => {
            villageSelect.value = button.dataset.villageId;
            checkSelectedVillage();
            document.querySelector('.checker-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    requestToggle.addEventListener('click', () => {
        requestForm.classList.toggle('show');
        if (requestForm.classList.contains('show')) {
            document.getElementById('request-name').focus();
        }
    });

    requestForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitButton = requestForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Menyimpan...';
        showFormMessage('', '');

        const payload = Object.fromEntries(new FormData(requestForm).entries());
        payload.latitude = payload.latitude || null;
        payload.longitude = payload.longitude || null;

        try {
            const response = await fetch(config.requestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const result = await response.json();

            if (!response.ok) {
                const firstError = result.errors ? Object.values(result.errors).flat()[0] : null;
                throw new Error(firstError || result.message || 'Pengajuan belum dapat disimpan.');
            }

            requestForm.reset();
            showFormMessage(`${result.message} Nomor pengajuan: #${result.request_id}.`, 'success');
        } catch (error) {
            showFormMessage(error.message, 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Kirim pengajuan';
        }
    });

    async function checkSelectedVillage() {
        const villageId = Number(villageSelect.value);
        const village = config.villages.find((item) => Number(item.id) === villageId);
        if (!village) {
            resultBox.classList.remove('show');
            return;
        }

        const latLng = L.latLng(Number(village.latitude), Number(village.longitude));
        map.flyTo(latLng, 15, { duration: .8 });
        setSelectedLocation(latLng.lat, latLng.lng, `${village.name}, ${village.district}`);
        await checkCoverage({ village_id: village.id }, latLng);
    }

    async function checkCoverage(payload, latLng) {
        setResultLoading();
        try {
            const response = await fetch(config.checkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Lokasi belum dapat diperiksa.');
            }

            updateSelectedMarker(latLng, result.status, result.label);
            showResult(result);
        } catch (error) {
            resultBadge.className = 'badge no';
            resultBadge.textContent = 'Gagal memeriksa';
            resultTitle.textContent = 'Terjadi kendala';
            resultDescription.textContent = error.message;
            resultDistance.textContent = '';
        }
    }

    function setResultLoading() {
        resultBox.classList.add('show');
        resultBadge.className = 'badge';
        resultBadge.textContent = 'Memeriksa...';
        resultTitle.textContent = 'Menghitung jarak lokasi';
        resultDescription.textContent = 'Mohon tunggu sebentar.';
        resultDistance.textContent = '';
        subscribeButton.style.display = 'none';
        requestToggle.style.display = 'none';
    }

    function showResult(result) {
        const meta = statusMeta[result.status] ?? statusMeta.unavailable;
        resultBadge.className = `badge ${meta.className}`;
        resultBadge.textContent = result.label;
        resultTitle.textContent = result.title;
        resultDescription.textContent = `${result.location}. ${result.description}`;
        resultDistance.textContent = `Jarak dari hub utama: ${formatDistance(result.distance_meters)}`;
        document.getElementById('request-coverage-status').value = result.status;

        const isAvailable = result.status === 'available';
        subscribeButton.style.display = isAvailable ? 'inline-flex' : 'none';
        requestToggle.style.display = isAvailable ? 'none' : 'inline-flex';
        if (isAvailable) {
            requestForm.classList.remove('show');
        }
    }

    function updateSelectedMarker(latLng, status, label) {
        if (selectedMarker) {
            map.removeLayer(selectedMarker);
        }
        const meta = statusMeta[status] ?? statusMeta.unavailable;
        selectedMarker = L.marker(latLng, { icon: markerIcon(meta.color, 18) })
            .addTo(map)
            .bindPopup(`<div class="map-popup"><strong>Lokasi pilihan</strong><br><small>${escapeHtml(label)}</small></div>`)
            .openPopup();
    }

    function setSelectedLocation(latitude, longitude, address) {
        document.getElementById('request-latitude').value = Number(latitude).toFixed(7);
        document.getElementById('request-longitude').value = Number(longitude).toFixed(7);
        document.getElementById('request-address').value = address;
    }

    function showFormMessage(message, type) {
        requestMessage.textContent = message;
        requestMessage.className = `form-message${type ? ` ${type}` : ''}`;
    }

    function formatDistance(meters) {
        return meters >= 1000 ? `${(meters / 1000).toFixed(2)} km` : `${meters} meter`;
    }

    function statusLabel(status) {
        return { available: 'Tersedia', expansion: 'Segera hadir', unavailable: 'Belum tersedia' }[status] || 'Belum tersedia';
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
        })[character]);
    }
});
