<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teknisi — BozonkNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/internal.css') }}">
</head>
<body>
    <main class="technician-page">
        <header class="technician-header">
            <div>
                <p class="kicker">Portal Teknisi</p>
                <h1>Survey & Marking Lokasi</h1>
                <span>{{ now()->format('d M Y') }}</span>
            </div>
            @if (auth()->user()?->role === 'admin')
                <a class="btn-outline" href="{{ route('internal.admin') }}">Kembali</a>
            @else
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-outline" type="submit">Logout</button>
                </form>
            @endif
        </header>

        <section class="technician-layout">
            <div class="task-column">
                <div class="task-section-head">
                    <div>
                        <p class="kicker">Data Marking</p>
                        <h2>Daftar ODP, ODC, dan Pelanggan</h2>
                    </div>
                    <span>{{ $markers->count() }} titik</span>
                </div>
                <div class="task-list">
                    @forelse ($markers as $marker)
                        @php
                            $markerType = strtoupper($marker->type);
                            $markerBadge = 'reject';

                            if ($markerType === 'ODP') {
                                $markerBadge = 'done';
                            } elseif ($markerType === 'ODC') {
                                $markerBadge = 'wait';
                            }
                        @endphp
                        <article class="task-card">
                            <div class="task-top">
                                <div>
                                    <span class="mono">{{ $marker->code ?: 'MARK-'.str_pad((string) $marker->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    <h2>{{ $marker->name }}</h2>
                                </div>
                                <span class="badge {{ $markerBadge }}">{{ $markerType }}</span>
                            </div>
                            <p>{{ $marker->address ?: $marker->notes ?: 'Lokasi tersimpan dari teknisi' }}</p>
                            <div class="task-meta">
                                <span>{{ $marker->status === 'active' ? 'Aktif' : ucfirst($marker->status ?: 'tersimpan') }}</span>
                                <span>{{ $marker->latitude && $marker->longitude ? number_format($marker->latitude, 5).', '.number_format($marker->longitude, 5) : 'Koordinat belum ada' }}</span>
                            </div>
                        </article>
                    @empty
                        <div class="empty-card">Belum ada data ODP, ODC, atau pelanggan dari teknisi.</div>
                    @endforelse
                </div>
            </div>

            <aside class="marking-panel">
                <div class="panel">
                    <div class="panel-head"><div><h2>Tambah Marking Map</h2><p>Untuk teknisi saat berada tepat di lokasi tiang/perangkat.</p></div></div>
                    @if (session('status'))
                        <p class="portal-alert success">{{ session('status') }}</p>
                    @endif
                    @if ($errors->any())
                        <p class="portal-alert error">{{ $errors->first() }}</p>
                    @endif
                    <form class="marking-form" action="{{ route('network-markers.store') }}" method="POST">
                        @csrf
                        <div class="field"><label for="marking-type">Jenis Titik</label><select id="marking-type" name="type" required><option>ODC</option><option>ODP</option><option>Pelanggan</option></select></div>
                        <div class="field"><label for="marking-odc">Hubungkan ke ODC</label><select id="marking-odc" name="odc_id"><option value="">Tidak pilih ODC</option>@foreach ($odcs as $odc)<option value="{{ $odc->id }}">{{ $odc->code }} — {{ $odc->name }}</option>@endforeach</select></div>
                        <div class="field"><label for="marking-odp">Hubungkan ke ODP</label><select id="marking-odp" name="odp_id"><option value="">Tidak pilih ODP</option>@foreach ($odps as $odp)<option value="{{ $odp->id }}">{{ $odp->code }} — {{ $odp->name }}</option>@endforeach</select></div>
                        <div class="field"><label for="marking-customer">Hubungkan ke Pelanggan</label><select id="marking-customer" name="customer_subscription_id"><option value="">Tidak pilih pelanggan</option>@foreach ($subscriptions as $subscription)<option value="{{ $subscription->id }}">{{ $subscription->registration_code }} — {{ $subscription->full_name }}</option>@endforeach</select></div>
                        <div class="field"><label for="marking-code">Kode Titik</label><input id="marking-code" name="code" type="text" placeholder="Contoh: ODP-BJG-012"></div>
                        <div class="field"><label for="marking-name">Nama Titik</label><input id="marking-name" name="name" type="text" placeholder="Contoh: ODP Bojonggede 012" required></div>
                        <div class="field"><label for="marking-address">Alamat / Patokan</label><input id="marking-address" name="address" type="text" placeholder="Dekat gang / patokan rumah"></div>
                        <div class="field"><label for="marking-notes">Catatan Teknisi</label><textarea id="marking-notes" name="notes" rows="3" placeholder="Kondisi tiang, port, atau catatan pemasangan"></textarea></div>
                        <input id="marking-latitude" type="hidden" name="latitude">
                        <input id="marking-longitude" type="hidden" name="longitude">
                        <div id="technician-map" class="technician-map"></div>
                        <div class="map-actions">
                            <button class="btn-outline" id="gps-button" type="button">Ambil GPS Saya</button>
                            <small id="map-status">Klik titik pada map atau ambil GPS.</small>
                        </div>
                        <div class="layer-filter" aria-label="Filter layer map">
                            <label><input type="checkbox" data-layer-toggle="ODC" checked> ODC</label>
                            <label><input type="checkbox" data-layer-toggle="ODP" checked> ODP</label>
                            <label><input type="checkbox" data-layer-toggle="Pelanggan" checked> Pelanggan</label>
                        </div>
                        <button class="btn btn-full" type="submit">Simpan Marking</button>
                    </form>
                </div>

                <div class="panel">
                    <div class="panel-head"><div><h2>Routing Kabel</h2><p>Simulasi garis jalur dari ODP ke pelanggan tanpa mengirim koordinat ke layanan luar.</p></div></div>
                    <div class="route-tool">
                        <div class="field">
                            <label for="route-odp">Titik ODP</label>
                            <select id="route-odp">
                                <option value="">Pilih ODP</option>
                                @foreach ($markers->where('type', 'ODP') as $marker)
                                    <option value="{{ $marker->latitude }},{{ $marker->longitude }}">{{ $marker->code ?: $marker->name }} — {{ $marker->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="route-customer">Titik Pelanggan</label>
                            <select id="route-customer">
                                <option value="">Pilih pelanggan</option>
                                @foreach ($markers->where('type', 'Pelanggan') as $marker)
                                    <option value="{{ $marker->latitude }},{{ $marker->longitude }}">{{ $marker->code ?: $marker->name }} — {{ $marker->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-full" id="route-button" type="button">Tampilkan Jalur</button>
                        <small id="route-status">Pilih ODP dan pelanggan untuk melihat simulasi jalur.</small>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head"><div><h2>ODP Referensi</h2><p>Referensi kapasitas titik aktif.</p></div></div>
                    <div class="mini-odp-list">
                        @forelse ($odps->take(5) as $odp)
                            @php
                                $availablePorts = max(0, $odp->total_ports - $odp->used_ports);
                            @endphp
                            <div><strong>{{ $odp->code }}</strong><span>{{ $availablePorts }} port tersedia</span></div>
                        @empty
                            <div><strong>Belum ada ODP</strong><span>Tambahkan data jaringan dahulu</span></div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </main>
    @php
        $technicianMarkers = $markers->map(function ($marker) {
            return [
            'type' => $marker->type,
            'name' => $marker->name,
            'code' => $marker->code,
            'address' => $marker->address,
            'latitude' => $marker->latitude,
            'longitude' => $marker->longitude,
            ];
        })->values();
    @endphp
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const technicianMarkers = @json($technicianMarkers);
        const defaultPoint = [-6.43587, 106.80106];
        const map = L.map('technician-map').setView(defaultPoint, 14);
        const statusText = document.getElementById('map-status');
        const latitudeInput = document.getElementById('marking-latitude');
        const longitudeInput = document.getElementById('marking-longitude');
        const routeStatus = document.getElementById('route-status');
        let marker = L.marker(defaultPoint, { draggable: true }).addTo(map);
        let routeLine = null;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const markerLayers = {
            ODC: L.layerGroup().addTo(map),
            ODP: L.layerGroup().addTo(map),
            Pelanggan: L.layerGroup().addTo(map),
        };

        function markerIcon(type) {
            const color = type === 'ODP' ? '#df5368' : type === 'ODC' ? '#e99511' : '#0878d1';
            return L.divIcon({
                className: '',
                html: `<span class="tech-marker" style="--marker-color:${color}"></span>`,
                iconSize: [22, 22],
                iconAnchor: [11, 11],
            });
        }

        technicianMarkers.forEach((item) => {
            if (!item.latitude || !item.longitude || !markerLayers[item.type]) return;

            L.marker([item.latitude, item.longitude], { icon: markerIcon(item.type) })
                .bindPopup(`<strong>${item.code || item.name}</strong><br>${item.type}<br>${item.address || 'Alamat belum diisi'}`)
                .addTo(markerLayers[item.type]);
        });

        document.querySelectorAll('[data-layer-toggle]').forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const layer = markerLayers[toggle.dataset.layerToggle];
                if (!layer) return;
                toggle.checked ? layer.addTo(map) : map.removeLayer(layer);
            });
        });

        function setPoint(lat, lng, message = 'Titik marking siap disimpan.') {
            latitudeInput.value = Number(lat).toFixed(7);
            longitudeInput.value = Number(lng).toFixed(7);
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], Math.max(map.getZoom(), 16));
            statusText.textContent = `${message} Koordinat: ${latitudeInput.value}, ${longitudeInput.value}`;
        }

        map.on('click', (event) => setPoint(event.latlng.lat, event.latlng.lng, 'Titik dari map dipilih.'));
        marker.on('dragend', () => {
            const point = marker.getLatLng();
            setPoint(point.lat, point.lng, 'Pin digeser.');
        });

        document.getElementById('gps-button').addEventListener('click', () => {
            if (!navigator.geolocation) {
                statusText.textContent = 'Browser belum mendukung GPS.';
                return;
            }

            statusText.textContent = 'Mengambil lokasi GPS...';
            navigator.geolocation.getCurrentPosition(
                (position) => setPoint(position.coords.latitude, position.coords.longitude, 'Lokasi GPS berhasil diambil.'),
                () => statusText.textContent = 'GPS gagal diambil. Klik titik pada map secara manual.',
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });

        document.getElementById('route-button').addEventListener('click', () => {
            const start = document.getElementById('route-odp').value;
            const end = document.getElementById('route-customer').value;

            if (!start || !end) {
                routeStatus.textContent = 'Pilih ODP dan pelanggan dulu.';
                return;
            }

            const startPoint = start.split(',').map(Number);
            const endPoint = end.split(',').map(Number);

            if (routeLine) map.removeLayer(routeLine);
            routeLine = L.polyline([startPoint, endPoint], {
                color: '#0878d1',
                weight: 5,
                opacity: 0.85,
                dashArray: '10 8',
            }).addTo(map);
            map.fitBounds(routeLine.getBounds(), { padding: [28, 28] });

            const distance = map.distance(startPoint, endPoint) / 1000;
            routeStatus.textContent = `Simulasi garis jalur ${distance.toFixed(2)} km.`;
        });
    </script>
</body>
</html>
