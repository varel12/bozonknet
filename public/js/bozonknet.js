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
    const subscriptionPage = document.getElementById('subscription-page');
    const subscriptionForm = document.getElementById('subscription-form');
    const subscriptionFormWrap = document.getElementById('subscription-form-wrap');
    const subscriptionConfirm = document.getElementById('subscription-confirm');
    const subscriptionMessage = document.getElementById('subscription-message');
    const subscriptionGpsMessage = document.getElementById('subscription-gps-message');
    const subscriptionConfirmAddress = document.getElementById('subscription-confirm-address');
    const subscriptionCitySearch = document.getElementById('subscription-city-search');
    const subscriptionRoadSearch = document.getElementById('subscription-road-search');
    const subscriptionVillage = document.getElementById('subscription-village');
    const subscriptionLatitude = document.getElementById('subscription-latitude');
    const subscriptionLongitude = document.getElementById('subscription-longitude');
    const subscriptionPlanCode = document.getElementById('subscription-plan-code');
    const subscriptionPlanSummary = document.getElementById('subscription-plan-summary');
    const subscriptionPlanPrice = document.getElementById('subscription-plan-price');
    const subscriptionCheckFields = document.querySelectorAll('[data-check-target]');
    const subscriptionSteps = document.querySelectorAll('[data-subscription-step]');
    const statusMeta = {
        available: { className: 'ok', color: '#16a36a' },
        expansion: { className: 'soon', color: '#e99511' },
        unavailable: { className: 'no', color: '#df5368' },
    };
    const plans = config.plans || {
        basic: { name: 'Internet Basic', speed: '20 Mbps', price: 165500 },
        standard: { name: 'Internet Standard', speed: '30 Mbps', price: 249750 },
        premium: { name: 'Internet Premium', speed: '50 Mbps', price: 299750 },
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
    let subscriptionMapMoving = false;
    let subscriptionAddressRequestId = 0;
    let subscriptionCoverageRequestId = 0;
    let subscriptionLocationStatus = null;
    let subscriptionLocationConfirmed = false;
    let subscriptionSelectedAddress = '';
    let currentCoverageResult = null;
    let subscriptionMap = null;


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

    document.querySelectorAll('[data-subscribe-plan]').forEach((button) => {
        button.addEventListener('click', () => openSubscriptionPage(button.dataset.subscribePlan));
    });

    subscribeButton.addEventListener('click', (event) => {
        event.preventDefault();
        openSubscriptionPage();
    });

    document.getElementById('subscription-close').addEventListener('click', closeSubscriptionPage);
    document.getElementById('subscription-finish').addEventListener('click', closeSubscriptionPage);

    document.querySelectorAll('[data-plan-code]').forEach((button) => {
        button.addEventListener('click', () => selectSubscriptionPlan(button.dataset.planCode));
    });

    document.getElementById('subscription-gps').addEventListener('click', useSubscriptionGps);
    subscriptionConfirmAddress.addEventListener('click', confirmSubscriptionAddress);
    subscriptionForm.addEventListener('submit', submitSubscription);
    subscriptionForm.addEventListener('input', updateSubscriptionChecks);
    subscriptionForm.addEventListener('change', updateSubscriptionChecks);
    updateSubscriptionChecks();

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
        currentCoverageResult = null;
        subscribeButton.style.display = 'none';
        requestToggle.style.display = 'none';
    }

    function showResult(result) {
        currentCoverageResult = result;
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

    function openSubscriptionPage(planCode = '') {
        subscriptionForm.reset();
        resetSubscriptionPlan();
        subscriptionFormWrap.classList.remove('hide');
        subscriptionConfirm.classList.remove('show');
        showSubscriptionMessage('', '');
        subscriptionGpsMessage.textContent = '';
        subscriptionGpsMessage.className = 'form-message';
        subscriptionVillage.value = '';
        subscriptionLatitude.value = '';
        subscriptionLongitude.value = '';
        subscriptionLocationStatus = null;
        subscriptionLocationConfirmed = false;
        subscriptionSelectedAddress = '';
        resetSubscriptionMapSearch();
        updateConfirmAddressButton();
        updateSubscriptionChecks();
        subscriptionPage.classList.add('show');
        subscriptionPage.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';


        setTimeout(initSubscriptionMap, 180);
        setTimeout(() => document.getElementById('subscription-name').focus(), 220);
    }

    function closeSubscriptionPage() {
        subscriptionPage.classList.remove('show');
        subscriptionPage.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }


    function resetSubscriptionPlan() {
        subscriptionPlanCode.value = '';
        document.querySelectorAll('[data-plan-code]').forEach((button) => {
            button.classList.remove('selected');
        });
        subscriptionPlanSummary.textContent = 'Belum dipilih';
        subscriptionPlanPrice.textContent = '-';
    }
    function selectSubscriptionPlan(planCode) {
        if (!(planCode in plans)) {
            resetSubscriptionPlan();
            updateSubscriptionChecks();
            return;
        }

        const selectedPlan = plans[planCode];
        subscriptionPlanCode.value = planCode;
        document.querySelectorAll('[data-plan-code]').forEach((button) => {
            button.classList.toggle('selected', button.dataset.planCode === subscriptionPlanCode.value);
        });
        subscriptionPlanSummary.textContent = `${selectedPlan.name} - ${selectedPlan.speed}`;
        subscriptionPlanPrice.textContent = `${formatRupiah(selectedPlan.price)}/bulan`;
        updateSubscriptionChecks();
    }

    function setSubscriptionLocation(latitude, longitude, message = 'Lokasi pemasangan dipilih dari peta.') {
        subscriptionLatitude.value = Number(latitude).toFixed(7);
        subscriptionLongitude.value = Number(longitude).toFixed(7);
        subscriptionLocationConfirmed = false;
        subscriptionSelectedAddress = '';
        resetSubscriptionMapSearch();
        updateConfirmAddressButton();
        updateSubscriptionChecks();

        if (!subscriptionMap) {
            return;
        }

        subscriptionMap.setView(L.latLng(latitude, longitude), 17);
        checkSubscriptionCoverage(latitude, longitude, message);
    }

    function updateSubscriptionLocationFromCenter(message = 'Titik pemasangan tersimpan.') {
        if (!subscriptionMap) {
            return;
        }

        const centerPoint = subscriptionMap.getCenter();
        subscriptionLatitude.value = centerPoint.lat.toFixed(7);
        subscriptionLongitude.value = centerPoint.lng.toFixed(7);
        subscriptionLocationConfirmed = false;
        subscriptionSelectedAddress = '';
        resetSubscriptionMapSearch();
        updateConfirmAddressButton();
        updateSubscriptionChecks();
        checkSubscriptionCoverage(centerPoint.lat, centerPoint.lng, message);
    }


    async function checkSubscriptionCoverage(latitude, longitude, fallbackMessage = 'Lokasi pemasangan dipilih.') {
        const requestId = ++subscriptionCoverageRequestId;
        subscriptionLocationStatus = null;
        subscriptionLocationConfirmed = false;
        subscriptionSelectedAddress = '';
        resetSubscriptionMapSearch();
        updateConfirmAddressButton();
        updateSubscriptionChecks();
        showSubscriptionMapMessage('Memeriksa jangkauan lokasi pemasangan...', '');

        try {
            const response = await fetch(config.checkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ latitude, longitude }),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Lokasi belum dapat diperiksa.');
            }
            if (requestId !== subscriptionCoverageRequestId) {
                return;
            }

            subscriptionLocationStatus = result.status;
            updateConfirmAddressButton();
            updateSubscriptionChecks();

            if (result.status !== 'available') {
                showSubscriptionMapMessage(`${result.label}: mohon maaf daerah belum tersedia.`, 'error');
                return;
            }

            showSubscriptionSelectedAddress(latitude, longitude, fallbackMessage);
        } catch (error) {
            if (requestId === subscriptionCoverageRequestId) {
                subscriptionLocationStatus = null;
                subscriptionLocationConfirmed = false;
                subscriptionSelectedAddress = '';
                updateConfirmAddressButton();
                updateSubscriptionChecks();
                showSubscriptionMapMessage(error.message || 'Lokasi belum dapat diperiksa.', 'error');
            }
        }
    }
    async function showSubscriptionSelectedAddress(latitude, longitude, fallbackMessage = 'Lokasi pemasangan dipilih.') {
        const requestId = ++subscriptionAddressRequestId;
        showSubscriptionMapMessage('Mencari nama jalan lokasi pemasangan...', '');

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}&zoom=18&addressdetails=1&accept-language=id`);
            if (!response.ok) {
                throw new Error('Alamat belum tersedia.');
            }

            const result = await response.json();
            if (requestId !== subscriptionAddressRequestId) {
                return;
            }

            fillSubscriptionMapSearch(result);
            const addressName = formatSubscriptionAddress(result);
            subscriptionSelectedAddress = addressName || fallbackMessage;
            updateConfirmAddressButton();
            showSubscriptionMapMessage(`Titik pemasangan: ${subscriptionSelectedAddress}. Klik Konfirmasi Alamat jika sudah benar.`, 'success');
        } catch (error) {
            if (requestId === subscriptionAddressRequestId) {
                subscriptionSelectedAddress = fallbackMessage;
                updateConfirmAddressButton();
                showSubscriptionMapMessage(`Titik pemasangan: ${subscriptionSelectedAddress}. Klik Konfirmasi Alamat jika sudah benar.`, 'success');
            }
        }
    }


    function updateConfirmAddressButton() {
        const canConfirm = Boolean(subscriptionLatitude.value && subscriptionLongitude.value);
        subscriptionConfirmAddress.disabled = !canConfirm;
        subscriptionConfirmAddress.textContent = subscriptionLocationConfirmed ? 'Alamat Dikonfirmasi' : 'Konfirmasi Alamat';
    }

    function confirmSubscriptionAddress() {
        if (subscriptionLocationStatus === null) {
            subscriptionLocationConfirmed = false;
            updateSubscriptionChecks();
            showSubscriptionMapMessage('Tunggu sebentar, sistem masih memeriksa jangkauan titik pemasangan.', 'error');
            return;
        }

        if (subscriptionLocationStatus !== 'available') {
            subscriptionLocationConfirmed = false;
            updateSubscriptionChecks();
            showSubscriptionMapMessage('Mohon maaf daerah belum tersedia.', 'error');
            return;
        }

        subscriptionLocationConfirmed = true;
        updateConfirmAddressButton();
        updateSubscriptionChecks();
        showSubscriptionMapMessage(`Titik pemasangan dikonfirmasi: ${subscriptionSelectedAddress || 'alamat pilihan Anda'}.`, 'success');
    }

    function resetSubscriptionMapSearch() {
        if (subscriptionCitySearch) {
            subscriptionCitySearch.value = '';
        }
        if (subscriptionRoadSearch) {
            subscriptionRoadSearch.value = '';
        }
    }

    function fillSubscriptionMapSearch(result) {
        const address = result.address || {};
        if (subscriptionCitySearch) {
            subscriptionCitySearch.value = address.city || address.county || address.town || address.city_district || '';
        }
        if (subscriptionRoadSearch) {
            subscriptionRoadSearch.value = address.road || address.residential || address.neighbourhood || address.hamlet || result.name || '';
        }
    }
    function formatSubscriptionAddress(result) {
        const address = result.address || {};
        const parts = [
            address.road || address.residential || address.neighbourhood || address.hamlet,
            address.village || address.suburb || address.city_district || address.town,
            address.city || address.county,
        ].filter(Boolean);

        return parts.length ? [...new Set(parts)].join(', ') : (result.name || result.display_name || '').split(',').slice(0, 3).join(', ');
    }

    function showSubscriptionMapMessage(message, type = 'success') {
        subscriptionGpsMessage.textContent = message;
        subscriptionGpsMessage.className = `form-message${type ? ` ${type}` : ''}`;
    }

    function initSubscriptionMap() {
        if (!subscriptionMap) {
            subscriptionMap = L.map('subscription-map', { scrollWheelZoom: true, zoomControl: true }).setView(center, 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(subscriptionMap);

            L.circle(center, {
                radius: Number(config.coverage.expansionRadius),
                color: '#e99511',
                weight: 2,
                dashArray: '7,7',
                fillColor: '#e99511',
                fillOpacity: .04,
            }).addTo(subscriptionMap);

            L.circle(center, {
                radius: Number(config.coverage.availableRadius),
                color: '#16a36a',
                weight: 2,
                fillColor: '#16a36a',
                fillOpacity: .12,
            }).addTo(subscriptionMap);

            config.odps.forEach((odp) => {
                const color = odp.status === 'planned' ? '#e99511' : '#0878d1';
                L.marker([Number(odp.latitude), Number(odp.longitude)], { icon: markerIcon(color, 14) })
                    .addTo(subscriptionMap)
                    .bindPopup(`<div class="map-popup"><strong>${escapeHtml(odp.code)}</strong><br><small>${escapeHtml(odp.name)}</small></div>`);
            });

            subscriptionMap.on('movestart', () => {
                subscriptionMapMoving = true;
                document.getElementById('subscription-map-picker')?.classList.add('moving');
            });

            subscriptionMap.on('moveend', () => {
                subscriptionMapMoving = false;
                document.getElementById('subscription-map-picker')?.classList.remove('moving');
                updateSubscriptionLocationFromCenter('Titik pemasangan tersimpan.');
            });

            subscriptionMap.on('click', (event) => {
                setSubscriptionLocation(event.latlng.lat, event.latlng.lng, 'Peta dipindahkan ke titik yang Anda klik.');
            });
        }

        subscriptionMap.invalidateSize();
        const latitude = Number(subscriptionLatitude.value);
        const longitude = Number(subscriptionLongitude.value);
        if (latitude && longitude) {
            setSubscriptionLocation(latitude, longitude);
        } else {
            updateSubscriptionLocationFromCenter('Geser peta sampai pin berada tepat di lokasi rumah.');
        }
    }

    function useSubscriptionGps() {
        if (!navigator.geolocation) {
            subscriptionGpsMessage.textContent = 'Browser ini tidak mendukung geolokasi. Silakan klik lokasi pada peta.';
            subscriptionGpsMessage.className = 'form-message error';
            return;
        }

        subscriptionGpsMessage.textContent = 'Meminta izin akses lokasi perangkat Anda...';
        subscriptionGpsMessage.className = 'form-message';
        navigator.geolocation.getCurrentPosition(
            (position) => {
                setSubscriptionLocation(position.coords.latitude, position.coords.longitude, 'Lokasi berhasil dideteksi dari GPS perangkat.');
            },
            () => {
                subscriptionGpsMessage.textContent = 'Izin lokasi ditolak atau tidak tersedia. Silakan klik lokasi pada peta.';
                subscriptionGpsMessage.className = 'form-message error';
            },
            { enableHighAccuracy: true, timeout: 8000 },
        );
    }

    async function submitSubscription(event) {
        event.preventDefault();
        const village = getVillageById(subscriptionVillage.value);
        if (subscriptionLocationStatus !== 'available' || !subscriptionLocationConfirmed) {
            showSubscriptionMessage('Pilih titik pemasangan yang tersedia lalu klik Konfirmasi Alamat terlebih dahulu.', 'error');
            return;
        }
        if (village && village.status !== 'available') {
            showSubscriptionMessage('Pendaftaran hanya bisa dikirim untuk desa yang berstatus tersedia.', 'error');
            return;
        }

        const submitButton = subscriptionForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Mengirim...';
        showSubscriptionMessage('', '');

        const payload = Object.fromEntries(new FormData(subscriptionForm).entries());
        payload.latitude = payload.latitude || null;
        payload.longitude = payload.longitude || null;

        try {
            const response = await fetch(config.subscriptionUrl, {
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
                throw new Error(firstError || result.message || 'Pendaftaran belum dapat dikirim.');
            }

            subscriptionFormWrap.classList.add('hide');
            subscriptionConfirm.classList.add('show');
        } catch (error) {
            showSubscriptionMessage(error.message, 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Kirim Pendaftaran';
        }
    }


    function updateSubscriptionChecks() {
        subscriptionCheckFields.forEach((field) => {
            const target = field.dataset.checkTarget;
            field.classList.toggle('is-complete', isSubscriptionFieldComplete(target));
        });

        subscriptionSteps.forEach((step) => {
            step.classList.toggle('is-complete', isSubscriptionStepComplete(step.dataset.subscriptionStep));
        });
    }

    function isSubscriptionStepComplete(step) {
        if (step === 'personal') {
            return [
                'subscription-name',
                'subscription-whatsapp',
                'subscription-email',
                'subscription-billing-day',
                'subscription-address',
            ].every(isSubscriptionFieldComplete);
        }

        if (step === 'location') {
            return isSubscriptionFieldComplete('subscription-location');
        }

        if (step === 'package' || step === 'cost') {
            return isSubscriptionFieldComplete('subscription-plan-code');
        }

        return false;
    }

    function isSubscriptionFieldComplete(target) {
        const input = document.getElementById(target);
        if (target === 'subscription-location') {
            return Boolean(subscriptionLatitude.value && subscriptionLongitude.value && subscriptionLocationStatus === 'available' && subscriptionLocationConfirmed);
        }

        if (target === 'subscription-plan-code') {
            return Boolean(subscriptionPlanCode.value);
        }

        if (!input) {
            return false;
        }

        if (input.type === 'email') {
            return input.value.trim() !== '' && input.checkValidity();
        }

        if (input.type === 'tel') {
            return input.value.replace(/\D/g, '').length >= 9;
        }

        return input.value.trim() !== '' && input.checkValidity();
    }
    function getVillageById(id) {
        return config.villages.find((item) => Number(item.id) === Number(id));
    }

    function showSubscriptionMessage(message, type) {
        subscriptionMessage.textContent = message;
        subscriptionMessage.className = `form-message${type ? ` ${type}` : ''}`;
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

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value).replace(/\s/g, '');
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
