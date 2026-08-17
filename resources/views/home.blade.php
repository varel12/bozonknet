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
                BozonkNet
            </a>
            <nav class="nav-links" aria-label="Navigasi utama">
                <a href="#beranda">Beranda</a>
                <a href="{{ route('login') }}">Login</a>
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

        <section class="company-about" id="tentang">
            <div class="wrap company-about-grid">
                <div class="company-story">
                    <div class="eyebrow"><span class="dot"></span> Tentang kami</div>
                    <h2>Tentang Dua Data Komunika - BozonkNet</h2>
                    <p>Dua Data Komunika merupakan perusahaan yang telah mengalami pertumbuhan dan perkembangan yang signifikan sebagai penyedia layanan di bidang Teknologi Informasi dan Komunikasi. Fokus utama perusahaan adalah memberikan pengalaman terbaik kepada pelanggan melalui layanan yang inovatif dan terdepan. Sebagai Internet Service Provider, kami hadir dengan kapasitas layanan informasi dan teknologi yang dapat sepenuhnya disesuaikan dengan kebutuhan individual pelanggan.</p>
                    <p>Dengan komitmen untuk menyediakan solusi yang handal dan efisien, perusahaan berusaha menjadi mitra terpercaya dalam mengoptimalkan infrastruktur teknologi bagi klien, memungkinkan mereka untuk fokus pada inti bisnis mereka tanpa khawatir tentang aspek teknologi.</p>
                    <a class="text-link" href="#kontak">Kenal lebih dekat dengan kami <span aria-hidden="true">→</span></a>
                </div>

                <div class="vision-accordion">
                    <details>
                        <summary><span>Visi</span><i aria-hidden="true"></i></summary>
                        <div>
                            <p>Menjadi pemimpin terkemuka dalam penyediaan layanan Teknologi Informasi dan Komunikasi yang inovatif, handal, dan berkelanjutan. Kami berkomitmen untuk menjadi mitra strategis bagi pelanggan, memajukan pertumbuhan bisnis mereka melalui solusi teknologi dan komunikasi data yang terdepan.</p>
                        </div>
                    </details>
                    <details>
                        <summary><span>Misi</span><i aria-hidden="true"></i></summary>
                        <div>
                            <p>Menyediakan layanan Komunikasi Data yang berkualitas tinggi, mengintegrasikan inovasi terkini, dan menawarkan solusi teknologi yang dapat disesuaikan dengan kebutuhan unik setiap pelanggan. Kami berusaha untuk menciptakan lingkungan kerja yang kreatif dan mendukung pengembangan potensi karyawan kami. Melalui kemitraan yang erat dengan pelanggan, kami bertujuan untuk meningkatkan efisiensi operasional, keamanan data, dan daya saing bisnis mereka di pasar yang terus berkembang.</p>
                        </div>
                    </details>
                </div>
            </div>
        </section>

        <section class="service-values" id="layanan">
            <div class="wrap">
                <div class="official-service-grid">
                    <article>
                        <h3>Cepat</h3>
                        <p>Sebagai pelopor teknologi terkini dalam ranah koneksi internet, kami membawa inovasi ke ujung jari Anda, memastikan setiap pengguna mendapatkan koneksi yang cepat dan terjangkau.</p>
                        <img src="{{ asset('images/services/cepat.jpg') }}" alt="Ilustrasi inovasi dan konektivitas digital" loading="lazy">
                    </article>
                    <article>
                        <h3>Handal</h3>
                        <p>mempersembahkan layanan internet yang dapat diandalkan, kami berkomitmen untuk memberikan pengalaman online yang tanpa gangguan kepada para pengguna kami.</p>
                        <img src="{{ asset('images/services/handal.jpg') }}" alt="Ilustrasi layanan teknologi yang handal" loading="lazy">
                    </article>
                    <article>
                        <h3>Berkualitas</h3>
                        <p>Dijalankan oleh para tim profesional dan infrastruktur canggih terkini, kami berkomitmen untuk menjadikan setiap pengalaman online Anda sebagai prioritas utama kami.</p>
                        <img src="{{ asset('images/services/berkualitas.jpg') }}" alt="Teknisi menangani infrastruktur jaringan" loading="lazy">
                    </article>
                </div>
            </div>
        </section>

        <section class="why-company">
            <div class="wrap why-company-grid">
                <div class="why-intro">
                    <h2>Kenapa Memilih Kami?</h2>
                    <p>Dengan keahlian dan pengalaman yang mendalam, kami menjamin pemberian layanan internet yang tak tertandingi. Kami memahami betapa pentingnya koneksi yang cepat, stabil, dan handal dalam menjalankan aktivitas online Anda. Kami dengan bangga menyajikan solusi yang menghadirkan kecepatan, stabilitas, dan kualitas terbaik. Memilih kami berarti memilih untuk terhubung dengan internet yang tidak hanya memenuhi, tetapi melampaui harapan Anda.</p>
                </div>
                <div class="why-accordion">
                    <details>
                        <summary>Pengalaman Bertahun-tahun</summary>
                        <p>Dengan pengalaman bertahun-tahun di dunia konektivitas internet, kami telah membangun fondasi yang kokoh dalam memahami esensi kualitas, kecepatan, dan kestabilan data. Sebagai pionir di industri ini, perjalanan panjang kami telah memberikan pemahaman mendalam tentang kebutuhan kritis pengguna terkait dengan layanan internet. Kami mengerti bahwa kualitas, kecepatan, dan kestabilan adalah unsur-unsur kunci yang membentuk pengalaman online yang memuaskan.</p>
                    </details>
                    <details>
                        <summary>Menggunakan Teknologi Terbaru</summary>
                        <p>Demi menyediakan layanan internet berkualitas tinggi, kami konsisten dalam mengadopsi teknologi terbaru sebagai inti dari operasional kami. Dengan tekad untuk selalu berada di garis terdepan inovasi, kami memastikan bahwa infrastruktur dan teknologi yang kami terapkan selalu mengikuti perkembangan terkini. Komitmen kami pada penggunaan teknologi terbaru mencerminkan tekad untuk terus berkembang, menghadirkan pengalaman online yang terbaik dan sesuai dengan kebutuhan masa kini.</p>
                    </details>
                    <details>
                        <summary>Teknisi Profesional</summary>
                        <p>Kami percaya melakukan kolaborasi dengan para profesional berpengalaman dalam bidang koneksi internet adalah kunci untuk menghadirkan layanan berkualitas tinggi. Tim kami terdiri dari individu-individu yang ahli dan berkomitmen, membawa pengetahuan mendalam dan keterampilan yang diperlukan untuk memastikan setiap pelanggan mendapatkan pengalaman terbaik dalam koneksi internet. Dengan bekerja sama secara erat dengan para ahli, kami dapat mengidentifikasi dan mengatasi tantangan teknis dengan cepat dan efisien.</p>
                    </details>
                </div>
            </div>
        </section>

        <section class="products" id="paket">
            <div class="wrap">
                <div class="section-head products-heading">
                    <div>
                        <div class="eyebrow"><span class="dot"></span> Produk kami</div>
                        <h2>Paket internet untuk setiap kebutuhan</h2>
                    </div>
                    <p>Pilih paket berdasarkan aktivitas harian Anda. Ketersediaan akhir tetap mengikuti hasil pengecekan lokasi dan kapasitas jaringan.</p>
                </div>
                <div class="plans">
                    @forelse ($packages as $package)
                        <article class="plan-card {{ $loop->iteration === 2 ? 'featured' : '' }}">
                            @if ($loop->iteration === 2)
                                <span class="plan-tag">Rekomendasi</span>
                            @endif
                            <span class="plan-name">{{ $package->name }}</span>
                            <div class="plan-speed"><strong>{{ $package->speed_mbps }}</strong><span>Mbps</span></div>
                            <p class="plan-description">{{ $package->description }}</p>
                            <div class="plan-price">Rp{{ number_format($package->price, 0, ',', '.') }}<small>/bulan</small></div>
                            <button class="plan-button" type="button" data-subscribe-plan="{{ $package->code }}">Paket</button>
                        </article>
                    @empty
                        <p class="empty-card">Paket internet belum tersedia.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="contact-section" id="kontak">
            <div class="wrap">
                <div class="contact-heading">
                    <h2>Hubungi Kami</h2>
                    <p>Kami siap membantu kebutuhan konektivitas Anda</p>
                </div>

                <div class="contact-grid contact-grid-single">
                    <div class="contact-left">
                        <div class="contact-info-panel">
                            <h3>Informasi Kontak</h3>

                            <div class="contact-detail">
                                <span class="contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
                                </span>
                                <div>
                                    <strong>Alamat</strong>
                                    <p>Terminal Bojonggede, Kp. Pos Muara RT01-RW05,<br>Bojonggede, Kab. Bogor, 16922</p>
                                </div>
                            </div>

                            <div class="contact-detail">
                                <span class="contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M6.6 10.8a15.7 15.7 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.7 3.8.7.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.7 21 3 13.3 3 3.7c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.7 3.8.1.4 0 .8-.3 1.1l-2.3 2.2Z"/></svg>
                                </span>
                                <div>
                                    <strong>Telepon</strong>
                                    <p><a href="https://wa.me/6281264644446" target="_blank" rel="noopener">+62812 6464 4446 (WhatsApp)</a><br><a href="https://wa.me/6281317191914" target="_blank" rel="noopener">+62813 1719 1914 (WhatsApp)</a></p>
                                </div>
                            </div>

                            <div class="contact-detail">
                                <span class="contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>
                                </span>
                                <div>
                                    <strong>Email</strong>
                                    <p><a href="mailto:info@duadata.id">info@duadata.id</a><br><a href="mailto:sales@duadata.id">sales@duadata.id</a></p>
                                </div>
                            </div>

                            <div class="contact-detail">
                                <span class="contact-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm4.2 14.2L11 13V7h2v4.9l4.2 2.5-1 1.8Z"/></svg>
                                </span>
                                <div>
                                    <strong>Jam Operasional</strong>
                                    <p>Senin - Jumat: 08:00 - 17:00 WIB<br>Sabtu: 08:00 - 12:00 WIB</p>
                                </div>
                            </div>
                        </div>

                        <iframe
                            class="contact-map"
                            title="Lokasi PT Dua Data Komunika"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d991.0590296879593!2d106.79333396958252!3d-6.4917584995937645!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69c315e096180d%3A0x6e14efe4fdd59b2b!2sBozonknet!5e0!3m2!1sid!2sid!4v1755841903883!5m2!1sid!2sid"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="subscription-page" id="subscription-page" aria-hidden="true">
        <div class="subscription-header">
            <button class="subscription-back" id="subscription-close" type="button">Kembali</button>
            <a class="logo" href="#beranda" aria-label="BozonkNet beranda">
                BozonkNet
            </a>
        </div>

        <section class="subscription-hero">
            <div class="wrap">
                <span class="eyebrow"><span class="dot"></span> Pendaftaran pelanggan</span>
                <h2>Lengkapi data berlangganan BozonkNet</h2>
                <p>Pilih paket, isi data pemasangan, lalu tim BozonkNet akan menghubungi Anda untuk konfirmasi akhir.</p>
            </div>
        </section>

        <div class="wrap subscription-body">
            <form class="subscription-form" id="subscription-form">
                                <div id="subscription-form-wrap" class="subscription-form-layout">
                    <aside class="subscription-sidebar" aria-label="Progress pendaftaran">
                        <div class="subscription-step" data-subscription-step="personal">
                            <span>Data Diri</span>
                            <i aria-hidden="true"></i>
                        </div>
                        <div class="subscription-step" data-subscription-step="location">
                            <span>Lokasi Pemasangan</span>
                            <i aria-hidden="true"></i>
                        </div>
                        <div class="subscription-step" data-subscription-step="package">
                            <span>Pilih Paket</span>
                            <i aria-hidden="true"></i>
                        </div>
                        <div class="subscription-step" data-subscription-step="cost">
                            <span>Biaya</span>
                            <i aria-hidden="true"></i>
                        </div>
                    </aside>
                    <div class="subscription-content">
                    <section class="subscription-section">
                        <div class="subscription-section-title">Isi Form Registrasi</div>
                        <p>Lengkapi data berikut agar tim BozonkNet dapat memproses pendaftaran Anda.</p>
                        <div class="subscription-grid two">
                            <div class="field checkable-field" data-check-target="subscription-name">
                                <label for="subscription-name">Nama*</label>
                                <input type="text" id="subscription-name" name="name" autocomplete="name" required placeholder="Nama lengkap">
                            </div>
                            <div class="field checkable-field" data-check-target="subscription-whatsapp">
                                <label for="subscription-whatsapp">NoHp/WA*</label>
                                <input type="tel" id="subscription-whatsapp" name="whatsapp" autocomplete="tel" required placeholder="08xx-xxxx-xxxx">
                            </div>
                        </div>
                        <div class="subscription-grid two">
                            <div class="field checkable-field" data-check-target="subscription-email">
                                <label for="subscription-email">Email*</label>
                                <input type="email" id="subscription-email" name="email" autocomplete="email" required placeholder="email@domain.com">
                            </div>
                            <div class="field checkable-field" data-check-target="subscription-billing-day">
                                <label for="subscription-billing-day">Tgl penagihan*</label>
                                <select id="subscription-billing-day" name="billing_day" required>
                                    @for ($day = 1; $day <= 10; $day++)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="field checkable-field" data-check-target="subscription-address">
                            <label for="subscription-address">Alamat*</label>
                            <textarea id="subscription-address" name="address" rows="4" required placeholder="Alamat lengkap pemasangan"></textarea>
                        </div>
                        <button class="btn-outline btn-full" type="button" id="subscription-gps">Gunakan Lokasi Saat Ini</button>
                        <div class="form-message" id="subscription-gps-message" aria-live="polite"></div>
                        <div class="choose-map-card checkable-field" id="subscription-map-picker" data-check-target="subscription-location">
                            <div class="choose-map-helper">
                                <strong>Pilih titik pemasangan</strong>
                                <span>Geser peta sampai pin biru berada tepat di lokasi rumah, atau klik titik di peta.</span>
                            </div>
                            <div id="subscription-map" aria-label="Peta lokasi pemasangan"></div>
                            <div class="choose-map-pin" aria-hidden="true">
                                <span></span>
                            </div>
                        </div>
                        <div class="map-search-row" aria-label="Pencarian alamat pemasangan">
                            <label class="map-search-field" for="subscription-city-search">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.8 4a6.8 6.8 0 1 1 0 13.6 6.8 6.8 0 0 1 0-13.6Zm0 2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6Zm5.2 9 4.3 4.3-1.4 1.4-4.3-4.3L16 15Z"/></svg>
                                <input type="text" id="subscription-city-search" placeholder="Pilih Kota / Kabupaten" autocomplete="address-level2">
                            </label>
                            <label class="map-search-field map-search-field-wide" for="subscription-road-search">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.8 4a6.8 6.8 0 1 1 0 13.6 6.8 6.8 0 0 1 0-13.6Zm0 2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6Zm5.2 9 4.3 4.3-1.4 1.4-4.3-4.3L16 15Z"/></svg>
                                <input type="text" id="subscription-road-search" placeholder="Tulis nama jalan / gedung / perumahan" autocomplete="street-address">
                            </label>
                        </div>
                        <button class="btn btn-full confirm-address-button" type="button" id="subscription-confirm-address" disabled>Konfirmasi Alamat</button>
                        <input type="hidden" id="subscription-village" name="village_id">
                        <input type="hidden" id="subscription-latitude" name="latitude">
                        <input type="hidden" id="subscription-longitude" name="longitude">

                        <div class="subscription-section-title compact">Paket internet*</div>
                        <div class="subscription-plans checkable-field" id="subscription-plans" data-check-target="subscription-plan-code">
                            @foreach ($packages as $package)
                                <button class="subscription-plan" type="button" data-plan-code="{{ $package->code }}">
                                    <span>{{ $package->name }}</span>
                                    <strong>{{ $package->speed_mbps }} Mbps</strong>
                                    <small>Rp{{ number_format($package->price, 0, ',', '.') }}/bulan</small>
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" id="subscription-plan-code" name="plan_code" value="">
                        <div class="billing-box">
                            <div><span>Paket dipilih</span><strong id="subscription-plan-summary">Belum dipilih</strong></div>
                            <div><span>Biaya bulanan</span><strong id="subscription-plan-price">-</strong></div>
                            <div><span>Biaya instalasi</span><strong>Rp0</strong></div>
                        </div>
                    </section>

                    <button class="btn btn-full subscription-submit" type="submit">Kirim Pendaftaran</button>
                    <div class="form-message" id="subscription-message" aria-live="polite"></div>
                </div>
                </div>

                <div class="subscription-confirm" id="subscription-confirm" aria-live="polite">
                    <strong>Pesanan sudah terkirim</strong>
                    <p>Terima kasih. Tim BozonkNet akan menghubungi Anda dalam 1x24 jam untuk validasi alamat dan jadwal pemasangan.</p>
                    <button class="btn" type="button" id="subscription-finish">Selesai</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div class="footer-company">
                <a href="#beranda" class="footer-logo">BozonkNet</a>
                <p>Penyedia layanan internet terpercaya dengan teknologi fiber optic dan wireless terdepan.</p>
                <div class="footer-socials" aria-label="Media sosial">
                    <a href="#" aria-label="Facebook">
                        <svg viewBox="0 0 24 24"><path d="M14 8h3V4h-3c-3 0-5 2-5 5v2H6v4h3v7h4v-7h3l1-4h-4V9c0-.6.4-1 1-1Z"/></svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg viewBox="0 0 24 24"><path d="M21 6.2c-.7.3-1.4.5-2.2.6a3.8 3.8 0 0 0 1.7-2.1c-.8.5-1.6.8-2.5 1a3.8 3.8 0 0 0-6.6 2.6c0 .3 0 .6.1.9A10.8 10.8 0 0 1 3.7 5.3a3.8 3.8 0 0 0 1.2 5.1c-.6 0-1.2-.2-1.7-.5 0 1.8 1.3 3.4 3.1 3.7-.3.1-.7.2-1 .2-.2 0-.5 0-.7-.1a3.8 3.8 0 0 0 3.6 2.7A7.7 7.7 0 0 1 3.5 18H2.6A10.8 10.8 0 0 0 8.5 20c7 0 10.9-5.8 10.9-10.9v-.5A7.7 7.7 0 0 0 21 6.2Z"/></svg>
                    </a>
                    <a href="#" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24"><path d="M6.5 8.5H3V21h3.5V8.5ZM4.8 3A2 2 0 1 0 4.8 7a2 2 0 0 0 0-4ZM21 14.2c0-3.8-2-5.9-4.8-5.9-2.2 0-3.2 1.2-3.8 2.1V8.5H9V21h3.5v-6.2c0-1.6.3-3.2 2.3-3.2s2.1 1.8 2.1 3.3V21H21v-6.8Z"/></svg>
                    </a>
                    <a href="#" aria-label="Instagram">
                        <svg viewBox="0 0 24 24"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm10.5 1.5a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/></svg>
                    </a>
                </div>
            </div>

            <div class="footer-column">
                <h3>Tautan Cepat</h3>
                <a href="#beranda">Beranda</a>
                <a href="#layanan">Layanan</a>
                <a href="#tentang">Tentang</a>
                <a href="#kontak">Kontak</a>
                <a href="#">Blog</a>
            </div>

            <div class="footer-column footer-contact">
                <h3>Kontak</h3>
                <p>
                    <svg viewBox="0 0 24 24"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Zm0-9a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
                    <span>Bojonggede, Kab.Bogor</span>
                </p>
                <a href="https://wa.me/6281264644446" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24"><path d="M6.6 10.8a15.7 15.7 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.7 3.8.7.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.7 21 3 13.3 3 3.7c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.7 3.8.1.4 0 .8-.3 1.1l-2.3 2.2Z"/></svg>
                    <span>+62812 6464 4446</span>
                </a>
                <a href="mailto:info@duadata.id">
                    <svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 0 0-2 2v12c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V6c0-1.1-.9-2-2-2Zm0 4-8 5-8-5V6l8 5 8-5v2Z"/></svg>
                    <span>info@duadata.id</span>
                </a>
            </div>
        </div>
        <div class="wrap footer-bottom">
            <p>©2025 PT Dua Data Komunika. All rights reserved.</p>
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
                    'name' => 'Area '.$odp->village_name,
                    'latitude' => $odp->latitude,
                    'longitude' => $odp->longitude,
                    'totalPorts' => $odp->total_ports,
                    'usedPorts' => $odp->used_ports,
                    'status' => $odp->status,
                ])->values(),
                'plans' => $packages->mapWithKeys(fn ($package) => [
                    $package->code => [
                        'name' => $package->name,
                        'speed' => $package->speed_mbps.' Mbps',
                        'price' => $package->price,
                    ],
                ]),
                'checkUrl' => route('coverage.check'),
                'requestUrl' => route('area-requests.store'),
                'subscriptionUrl' => route('subscriptions.store'),
            ];
        @endphp
        <script type="application/json" id="map-data">{!! json_encode($mapPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
        <script src="{{ asset('js/bozonknet.js') }}" defer></script>
    @endif
</body>
</html>
