<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Cek cakupan layanan internet BozonkNet di Bojonggede dan ajukan perluasan area secara online.">
    <title>BozonkNet — Internet Lokal Bojonggede</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/bozonknet.css') }}">
</head>
<body>
    <header>
        <div class="nav wrap">
            <a class="logo" href="#beranda" aria-label="BozonkNet beranda">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true">
                    <circle cx="14" cy="14" r="13" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M7 15a10 10 0 0 1 14 0M10 18a6 6 0 0 1 8 0" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="14" cy="21" r="1.7" fill="currentColor"/>
                </svg>
                BozonkNet
            </a>
            <nav class="nav-links" aria-label="Navigasi utama">
                <a href="#beranda">Beranda</a>
                <a href="#cek-jangkauan">Cek Jangkauan</a>
                <a href="#paket">Paket</a>
                <a href="#tentang">Tentang</a>
            </nav>
            <a class="nav-cta" href="#cek-jangkauan">Cek Jangkauan</a>
        </div>
    </header>

    <main>
        <section class="hero" id="beranda">
            <div class="wrap hero-grid">
                <div>
                    <div class="eyebrow"><span class="dot"></span> PT Dua Data Komunika</div>
                    <h1>Internet lokal untuk warga <span>Bojonggede</span> dan sekitarnya</h1>
                    <p class="lead">Cek ketersediaan jaringan langsung dari peta, temukan status desa Anda, dan ajukan lokasi yang belum terjangkau kepada tim BozonkNet.</p>
                    <div class="hero-actions">
                        <a class="btn" href="#cek-jangkauan">Cek lokasi saya <span aria-hidden="true">→</span></a>
                        <a class="btn-outline" href="#paket">Lihat paket</a>
                    </div>
                    <div class="stat-strip">
                        <div><div class="stat-num">{{ $villages->count() }}</div><div class="stat-label">Desa terdata</div></div>
                        <div><div class="stat-num">{{ $odps->where('status', 'active')->count() }}</div><div class="stat-label">Hub / ODP aktif</div></div>
                        <div><div class="stat-num">24/7</div><div class="stat-label">Pemantauan jaringan</div></div>
                    </div>
                </div>
                <div class="hero-orbit" aria-hidden="true">
                    <div class="orbit orbit-one"></div>
                    <div class="orbit orbit-two"></div>
                    <div class="orbit-core"><span></span><strong>BozonkNet</strong><small>Local Network</small></div>
                </div>
            </div>
        </section>

        <section id="cek-jangkauan">
            <div class="wrap">
                <div class="section-head">
                    <div class="eyebrow"><span class="dot"></span> Peta cakupan</div>
                    <h2>Pastikan jaringan tersedia sebelum berlangganan</h2>
                    <p>Klik titik pemasangan pada peta atau pilih desa. Sistem menghitung jarak dari hub aktif dan menampilkan status cakupan saat ini.</p>
                </div>

                @if (!$coverageArea)
                    <div class="alert alert-error">Data cakupan belum tersedia. Jalankan database seeder terlebih dahulu.</div>
                @else
                    <div class="coverage-layout">
                        <div>
                            <div id="map" aria-label="Peta cakupan BozonkNet"></div>
                            <div class="legend">
                                <span><i class="legend-available"></i> Area tersedia</span>
                                <span><i class="legend-expansion"></i> Area perluasan</span>
                                <span><i class="legend-odp"></i> Titik hub / ODP</span>
                            </div>
                            <p class="map-hint"><strong>Tip:</strong> perbesar peta lalu klik lokasi rumah untuk melakukan pengecekan.</p>

                            <div class="area-list">
                                @foreach ($villages as $village)
                                    @php
                                        $statusClass = match ($village->status) {
                                            'available' => 'ok',
                                            'expansion' => 'soon',
                                            default => 'no',
                                        };
                                        $statusLabel = match ($village->status) {
                                            'available' => 'Tersedia',
                                            'expansion' => 'Segera hadir',
                                            default => 'Belum tersedia',
                                        };
                                    @endphp
                                    <button class="area-row" type="button" data-village-id="{{ $village->id }}">
                                        <span class="area-name"><i class="pin {{ $statusClass }}"></i>{{ $village->name }}</span>
                                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <aside class="checker-card">
                            <span class="card-number mono">01 / CHECK COVERAGE</span>
                            <h3>Cek berdasarkan desa</h3>
                            <p class="sub">Pilih desa tempat tinggal Anda di Kecamatan Bojonggede.</p>
                            <div class="field">
                                <label for="village-select">Desa / Kelurahan</label>
                                <select id="village-select">
                                    <option value="">— Pilih desa —</option>
                                    @foreach ($villages as $village)
                                        <option value="{{ $village->id }}">{{ $village->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-full" id="check-village" type="button">Cek sekarang</button>

                            <div class="result-box" id="coverage-result" aria-live="polite">
                                <span class="badge" id="result-badge"></span>
                                <div class="result-title" id="result-title"></div>
                                <div class="result-desc" id="result-description"></div>
                                <div class="distance" id="result-distance"></div>
                                <a class="btn btn-full result-subscribe" id="subscribe-button" href="#paket">Lihat paket</a>
                                <button class="btn-ghost" id="request-toggle" type="button">Ajukan area saya</button>
                            </div>

                            <form id="area-request-form" class="area-request-form">
                                <div class="form-heading">
                                    <strong>Pengajuan area</strong>
                                    <span>Lokasi akan ditinjau oleh tim jaringan.</span>
                                </div>
                                <div class="field">
                                    <label for="request-name">Nama lengkap</label>
                                    <input type="text" id="request-name" name="name" autocomplete="name" required placeholder="Nama Anda">
                                </div>
                                <div class="field">
                                    <label for="request-address">Desa / alamat pemasangan</label>
                                    <input type="text" id="request-address" name="address" required placeholder="Contoh: Pabuaran, Bojonggede">
                                </div>
                                <div class="field">
                                    <label for="request-whatsapp">Nomor WhatsApp</label>
                                    <input type="tel" id="request-whatsapp" name="whatsapp" autocomplete="tel" required placeholder="08xx-xxxx-xxxx">
                                </div>
                                <input type="hidden" id="request-latitude" name="latitude">
                                <input type="hidden" id="request-longitude" name="longitude">
                                <input type="hidden" id="request-coverage-status" name="coverage_status" value="unavailable">
                                <button class="btn btn-full" type="submit">Kirim pengajuan</button>
                                <div class="form-message" id="request-message" aria-live="polite"></div>
                            </form>
                        </aside>
                    </div>
                @endif
            </div>
        </section>

        <section class="why">
            <div class="wrap">
                <div class="section-head compact">
                    <div class="eyebrow"><span class="dot"></span> Kenapa BozonkNet</div>
                    <h2>Layanan yang tumbuh bersama Bojonggede</h2>
                </div>
                <div class="why-grid">
                    <article class="why-card"><span class="num">01</span><h3>Jaringan lokal</h3><p>Dikelola dari Bojonggede sehingga penanganan gangguan lebih dekat dan terarah.</p></article>
                    <article class="why-card"><span class="num">02</span><h3>Cakupan transparan</h3><p>Status lokasi dapat diperiksa lebih awal melalui peta sebelum proses pemasangan.</p></article>
                    <article class="why-card"><span class="num">03</span><h3>Layanan fleksibel</h3><p>Paket dapat disesuaikan untuk kebutuhan rumah, usaha kecil, maupun bisnis.</p></article>
                    <article class="why-card"><span class="num">04</span><h3>Perluasan berbasis data</h3><p>Pengajuan pelanggan menjadi masukan prioritas pembangunan jaringan berikutnya.</p></article>
                </div>
            </div>
        </section>

        <section id="paket">
            <div class="wrap">
                <div class="section-head">
                    <div class="eyebrow"><span class="dot"></span> Paket layanan</div>
                    <h2>Pilih kecepatan sesuai kebutuhan</h2>
                    <p>Ketersediaan paket mengikuti hasil pengecekan lokasi dan kapasitas jaringan di lapangan.</p>
                </div>
                <div class="plans">
                    <article class="plan-card"><span class="plan-name">Hemat</span><div class="plan-price">Rp135rb<small>/bulan</small></div><strong>Hingga 20 Mbps</strong><ul><li>1–3 pengguna</li><li>Tanpa batas kuota</li><li>Dukungan teknis lokal</li></ul><a href="#cek-jangkauan" class="plan-button">Cek ketersediaan</a></article>
                    <article class="plan-card featured"><span class="plan-tag">Paling populer</span><span class="plan-name">Keluarga</span><div class="plan-price">Rp220rb<small>/bulan</small></div><strong>Hingga 50 Mbps</strong><ul><li>4–8 pengguna</li><li>Streaming dan gaming</li><li>Prioritas dukungan</li></ul><a href="#cek-jangkauan" class="plan-button">Cek ketersediaan</a></article>
                    <article class="plan-card"><span class="plan-name">Bisnis</span><div class="plan-price">Rp399rb<small>/bulan</small></div><strong>Hingga 100 Mbps</strong><ul><li>Untuk operasional usaha</li><li>IP statis tersedia</li><li>Dukungan teknis 24/7</li></ul><a href="#cek-jangkauan" class="plan-button">Cek ketersediaan</a></article>
                </div>
            </div>
        </section>

        <section class="about" id="tentang">
            <div class="wrap about-grid">
                <div>
                    <div class="eyebrow"><span class="dot"></span> Tentang kami</div>
                    <h2>PT Dua Data Komunika</h2>
                </div>
                <div>
                    <p>BozonkNet hadir untuk memenuhi kebutuhan layanan internet masyarakat Bojonggede dan sekitarnya dengan infrastruktur yang dikembangkan secara bertahap, terukur, dan dekat dengan pelanggan.</p>
                    <dl class="company-data">
                        <div><dt>Alamat</dt><dd>Kp. Pos, RT 05/RW 01, Desa Bojonggede, Kabupaten Bogor</dd></div>
                        <div><dt>Layanan</dt><dd>Internet rumah dan bisnis, jaringan fiber optik lokal</dd></div>
                        <div><dt>Area kerja</dt><dd>Kecamatan Bojonggede dan desa sekitarnya</dd></div>
                    </dl>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="wrap foot-grid">
            <div class="foot-copy">© {{ date('Y') }} BOZONKNET — PT DUA DATA KOMUNIKA</div>
            <div class="foot-links"><a href="#beranda">Beranda</a><a href="#cek-jangkauan">Cakupan</a><a href="#paket">Paket</a></div>
        </div>
    </footer>

    @if ($coverageArea)
        @php
            $mapPayload = [
                'coverage' => [
                    'name' => $coverageArea->name,
                    'latitude' => $coverageArea->center_latitude,
                    'longitude' => $coverageArea->center_longitude,
                    'availableRadius' => $coverageArea->available_radius_meters,
                    'expansionRadius' => $coverageArea->expansion_radius_meters,
                ],
                'villages' => $villages->map(fn ($village) => [
                    'id' => $village->id,
                    'name' => $village->name,
                    'district' => $village->district,
                    'latitude' => $village->latitude,
                    'longitude' => $village->longitude,
                    'status' => $village->status,
                ])->values(),
                'odps' => $odps->map(fn ($odp) => [
                    'code' => $odp->code,
                    'name' => $odp->name,
                    'latitude' => $odp->latitude,
                    'longitude' => $odp->longitude,
                    'totalPorts' => $odp->total_ports,
                    'usedPorts' => $odp->used_ports,
                    'status' => $odp->status,
                ])->values(),
                'checkUrl' => route('coverage.check'),
                'requestUrl' => route('area-requests.store'),
            ];
        @endphp
        <script type="application/json" id="map-data">{!! json_encode($mapPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
        <script src="{{ asset('js/bozonknet.js') }}" defer></script>
    @endif
</body>
</html>
