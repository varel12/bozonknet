<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — BozonkNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/internal.css') }}">
</head>
<body>
    @php
        $adminPages = [
            'dashboard' => 'Dashboard Admin',
            'pendaftaran' => 'Pendaftaran',
            'area-dicari' => 'Area Dicari',
            'pengajuan' => 'Pengajuan Area',
            'packages' => 'Paket Internet',
            'jaringan' => 'Jaringan ODP',
            'topology' => 'Topologi',
            'users' => 'User',
        ];
        $activeAdminPage = array_key_exists(request('page'), $adminPages) ? request('page') : 'dashboard';
    @endphp
    <div class="internal-shell">
        <aside class="sidebar" id="admin-sidebar">
            <a class="sidebar-logo text-logo" href="{{ route('home') }}">BozonkNet</a>
            <nav class="sidebar-nav" aria-label="Navigasi admin">
                <span class="nav-label">Menu Utama</span>
                <button class="{{ $activeAdminPage === 'dashboard' ? 'active' : '' }}" type="button" data-admin-target="dashboard">Dashboard</button>
                <button class="{{ $activeAdminPage === 'pendaftaran' ? 'active' : '' }}" type="button" data-admin-target="pendaftaran">Pendaftaran</button>
                <button class="{{ $activeAdminPage === 'area-dicari' ? 'active' : '' }}" type="button" data-admin-target="area-dicari">Area Dicari</button>
                <button class="{{ $activeAdminPage === 'pengajuan' ? 'active' : '' }}" type="button" data-admin-target="pengajuan">Pengajuan Area</button>
                <button class="{{ $activeAdminPage === 'packages' ? 'active' : '' }}" type="button" data-admin-target="packages">Paket Internet</button>
                <button class="{{ $activeAdminPage === 'jaringan' ? 'active' : '' }}" type="button" data-admin-target="jaringan">Jaringan ODP</button>
                <button class="{{ $activeAdminPage === 'topology' ? 'active' : '' }}" type="button" data-admin-target="topology">Topologi</button>
                <button class="{{ $activeAdminPage === 'users' ? 'active' : '' }}" type="button" data-admin-target="users">User</button>
            </nav>
            <div class="sidebar-user">
                <span>{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                <div><strong>{{ auth()->user()->name }}</strong><small>Monitoring & laporan</small></div>
            </div>
        </aside>

        <main class="internal-main">
            <header class="internal-topbar">
                <button class="sidebar-burger" type="button" aria-label="Tutup sidebar" aria-expanded="true" aria-controls="admin-sidebar">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div>
                    <p class="kicker">Portal Internal</p>
                    <h1>{{ $adminPages[$activeAdminPage] }}</h1>
                </div>
                <div class="topbar-actions">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn" type="submit">Logout</button>
                    </form>
                </div>
            </header>

            <section class="content">
                @if (session('status'))
                    <p class="portal-alert success">{{ session('status') }}</p>
                @endif
                @if ($errors->any())
                    <p class="portal-alert error">{{ $errors->first() }}</p>
                @endif

                <section class="admin-page {{ $activeAdminPage === 'dashboard' ? 'active' : '' }}" data-admin-page="dashboard">
                    <div class="stat-grid">
                        <article class="stat-card"><span>Total Pendaftaran</span><strong>{{ $stats['subscriptions'] }}</strong><small>Pelanggan yang isi form paket</small></article>
                        <article class="stat-card"><span>Pengajuan Area</span><strong class="accent">{{ $stats['areaRequests'] }}</strong><small>Lokasi belum/kurang terjangkau</small></article>
                        <article class="stat-card"><span>Menunggu Diproses</span><strong class="amber">{{ $stats['pendingRequests'] }}</strong><small>Butuh follow up admin</small></article>
                        <article class="stat-card"><span>ODP Aktif</span><strong class="green">{{ $stats['activeOdps'] }}</strong><small>Titik jaringan siap layanan</small></article>
                        <article class="stat-card"><span>Paket Aktif</span><strong class="accent">{{ $stats['packages'] ?? 0 }}</strong><small>Paket tampil di halaman publik</small></article>
                    </div>
                </section>

                <section class="panel admin-page {{ $activeAdminPage === 'area-dicari' ? 'active' : '' }}" data-admin-page="area-dicari">
                    <div class="panel-head">
                        <div>
                            <h2>Area yang Sering Dicari Pelanggan</h2>
                            <p>Grafik ini diambil dari lokasi pendaftaran pelanggan, bukan dari pengajuan area.</p>
                        </div>
                        <span class="pill">{{ $areaSummary->sum('total') }} pencarian</span>
                    </div>
                    <div class="bar-chart">
                        @forelse ($areaSummary as $area)
                            @php($max = max(1, $areaSummary->max('total')))
                            <div class="bar-row">
                                <span>{{ $area['label'] }}</span>
                                <div><i style="width: {{ ($area['total'] / $max) * 100 }}%"></i></div>
                                <strong>{{ $area['total'] }}</strong>
                            </div>
                        @empty
                            <p class="empty-card">Belum ada data lokasi yang bisa dibuat grafik.</p>
                        @endforelse
                    </div>
                </section>

                <section class="panel admin-page {{ $activeAdminPage === 'pendaftaran' ? 'active' : '' }}" data-admin-page="pendaftaran">
                    <div class="panel-head"><div><h2>Pendaftaran Pelanggan</h2><p>Data dari form berlangganan customer.</p></div><span class="pill">{{ $subscriptions->count() }} terbaru</span></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Customer</th><th>Alamat</th><th>Paket</th><th>Tagihan</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($subscriptions as $subscription)
                                    <tr>
                                        <td><strong>{{ $subscription->name }}</strong><small>{{ $subscription->whatsapp }} · {{ $subscription->email }}</small></td>
                                        <td>{{ $subscription->full_address ?: $subscription->street_address }}<small>{{ $subscription->village?->name ?: 'Desa belum terdeteksi' }}</small></td>
                                        <td>{{ $subscription->plan_name ?: strtoupper((string) $subscription->plan_code) }}</td>
                                        <td>Tgl {{ $subscription->billing_day }}</td>
                                        <td><span class="badge wait">{{ $subscription->status ?: 'baru' }}</span></td>
                                        <td>
                                            @php($waNumber = preg_replace('/\D+/', '', (string) $subscription->whatsapp))
                                            @if ($waNumber)
                                                <a class="whatsapp-action" href="https://wa.me/{{ str_starts_with($waNumber, '0') ? '62'.substr($waNumber, 1) : $waNumber }}?text={{ urlencode('Halo '.$subscription->name.', kami dari BozonkNet ingin konfirmasi pendaftaran paket '.$subscription->plan_name.'.') }}" target="_blank" rel="noopener" aria-label="Chat WhatsApp {{ $subscription->name }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2a9.88 9.88 0 0 0-8.55 14.82L2 22l5.33-1.4A9.95 9.95 0 1 0 12.04 2Zm0 18.15a8.2 8.2 0 0 1-4.18-1.14l-.3-.18-3.16.83.84-3.08-.2-.32a8.16 8.16 0 1 1 7 3.9Zm4.49-6.12c-.25-.12-1.47-.72-1.7-.8-.23-.08-.39-.12-.56.12-.16.25-.64.8-.78.96-.14.17-.29.19-.54.07-.25-.12-1.04-.38-1.98-1.21-.73-.65-1.23-1.46-1.37-1.7-.14-.25-.02-.38.1-.5.11-.1.25-.29.37-.43.12-.14.16-.25.25-.42.08-.16.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.42h-.48c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07 0 1.22.9 2.4 1.02 2.56.12.17 1.76 2.68 4.26 3.76.6.26 1.06.41 1.42.53.6.19 1.14.16 1.57.1.48-.07 1.47-.6 1.68-1.18.2-.58.2-1.08.14-1.18-.06-.1-.23-.16-.48-.29Z"/></svg>
                                                    Chat
                                                </a>
                                            @else
                                                <span class="empty-action">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty-row">Belum ada pendaftaran pelanggan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel admin-page {{ $activeAdminPage === 'pengajuan' ? 'active' : '' }}" data-admin-page="pengajuan">
                    <div class="panel-head"><div><h2>Pengajuan Area Belum Terjangkau</h2><p>Dipakai untuk melihat lokasi dengan permintaan tinggi.</p></div><span class="pill">{{ $areaRequests->count() }} terbaru</span></div>
                    <div class="request-grid">
                        @forelse ($areaRequests as $request)
                            <article class="request-card">
                                <div><strong>{{ $request->name }}</strong><span class="badge {{ $request->coverage_status === 'available' ? 'done' : 'reject' }}">{{ $request->coverage_status }}</span></div>
                                <p>{{ $request->address }}</p>
                                <small>{{ $request->whatsapp }} · {{ $request->created_at?->diffForHumans() }}</small>
                            </article>
                        @empty
                            <p class="empty-card">Belum ada pengajuan area.</p>
                        @endforelse
                    </div>
                </section>

                <section class="panel admin-page {{ $activeAdminPage === 'packages' ? 'active' : '' }}" data-admin-page="packages">
                    <div class="panel-head"><div><h2>Paket Internet</h2><p>CRUD paket yang tampil di halaman publik.</p></div><span class="pill">{{ $packages->count() }} paket</span></div>
                    <form class="user-form" method="POST" action="{{ route('internet-packages.store') }}">
                        @csrf
                        <input name="code" type="text" placeholder="kode: basic" required>
                        <input name="name" type="text" placeholder="Nama paket" required>
                        <input name="speed_mbps" type="number" min="1" placeholder="Mbps" required>
                        <input name="price" type="number" min="0" placeholder="Harga" required>
                        <select name="is_active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select>
                        <button class="btn" type="submit">Tambah Paket</button>
                    </form>
                    <div class="table-wrap user-edit-table">
                        <table>
                            <thead><tr><th>Kode</th><th>Nama</th><th>Mbps</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($packages as $package)
                                    <tr>
                                        <form method="POST" action="{{ route('internet-packages.update', $package) }}">
                                            @csrf
                                            @method('PUT')
                                            <td><input name="code" value="{{ $package->code }}" required></td>
                                            <td><input name="name" value="{{ $package->name }}" required></td>
                                            <td><input name="speed_mbps" type="number" value="{{ $package->speed_mbps }}" required></td>
                                            <td><input name="price" type="number" value="{{ $package->price }}" required></td>
                                            <td>
                                                <select name="is_active">
                                                    <option value="1" @selected($package->is_active)>Aktif</option>
                                                    <option value="0" @selected(! $package->is_active)>Nonaktif</option>
                                                </select>
                                            </td>
                                            <td class="inline-actions">
                                                <button class="btn" type="submit">Simpan</button>
                                        </form>
                                                <form method="POST" action="{{ route('internet-packages.destroy', $package) }}" onsubmit="return confirm('Hapus paket ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn-danger" type="submit">Hapus</button>
                                                </form>
                                            </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty-row">Belum ada paket internet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="panel admin-page {{ $activeAdminPage === 'jaringan' ? 'active' : '' }}" data-admin-page="jaringan">
                    <div class="panel-head"><div><h2>Jaringan ODP / ODC / Pelanggan</h2><p>Monitoring data ODP lama dan marking baru dari teknisi.</p></div></div>
                    <div class="odp-grid">
                        @foreach ($markers as $marker)
                            <article class="odp-card">
                                <span class="mono">{{ $marker->type }} · {{ $marker->code ?: 'MARK-'.$marker->id }}</span>
                                <h3>{{ $marker->name }}</h3>
                                <p>{{ $marker->address ?: $marker->notes ?: 'Lokasi tersimpan dari teknisi' }}</p>
                                <small>{{ $marker->latitude && $marker->longitude ? $marker->latitude.', '.$marker->longitude : 'Koordinat belum ada' }}</small>
                                @if (strtoupper($marker->type) === 'ODP')
                                    <form class="odp-card-actions" method="POST" action="{{ route('network-markers.odp.destroy', $marker) }}" onsubmit="return confirm('Hapus ODP ini dari data teknisi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger" type="submit">Hapus ODP</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                        @forelse ($odps as $odp)
                            @php($availablePorts = max(0, $odp->total_ports - $odp->used_ports))
                            <article class="odp-card">
                                <span class="mono">{{ $odp->code }}</span>
                                <h3>{{ $odp->name }}</h3>
                                <p>{{ $odp->address }}</p>
                                <div class="port-bar"><i style="width: {{ $odp->total_ports ? min(100, ($odp->used_ports / $odp->total_ports) * 100) : 0 }}%"></i></div>
                                <small>{{ $availablePorts }} port tersedia dari {{ $odp->total_ports }}</small>
                                <form class="odp-card-actions" method="POST" action="{{ route('odps.destroy', $odp) }}" onsubmit="return confirm('Hapus ODP ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-danger" type="submit">Hapus ODP</button>
                                </form>
                            </article>
                        @empty
                            @if ($markers->isEmpty())
                                <p class="empty-card">Belum ada data jaringan.</p>
                            @endif
                        @endforelse
                    </div>
                </section>

                <section class="panel admin-page {{ $activeAdminPage === 'topology' ? 'active' : '' }}" data-admin-page="topology">
                    <div class="panel-head"><div><h2>Provisioning OLT → ODC → ODP</h2><p>Khusus Super Admin untuk membuat placeholder topologi jaringan.</p></div><span class="pill">{{ $olts->count() }} OLT</span></div>
                    @if (auth()->user()?->role === 'super_admin')
                        <form class="user-form" method="POST" action="{{ route('network-provisioning.store') }}">
                            @csrf
                            <input name="name" type="text" placeholder="Nama OLT" required>
                            <input name="ip_address" type="text" placeholder="IP address opsional">
                            <input name="location_description" type="text" placeholder="Lokasi OLT">
                            <input name="odc_count" type="number" min="1" value="1" required>
                            <input name="odp_per_odc" type="number" min="1" value="4" required>
                            <input name="ports_per_odp" type="number" min="1" value="8" required>
                            <button class="btn" type="submit">Generate</button>
                        </form>
                    @else
                        <p class="empty-card">Provisioning hanya bisa dilakukan oleh Super Admin.</p>
                    @endif
                    <div class="request-grid">
                        @forelse ($olts as $olt)
                            <article class="request-card">
                                <div><strong>{{ $olt->name }}</strong><span class="badge done">{{ $olt->odcs_count }} ODC</span></div>
                                <p>{{ $olt->location_description ?: 'Lokasi belum diisi' }}</p>
                                <small>{{ $olt->ip_address ?: 'IP belum diisi' }}</small>
                            </article>
                        @empty
                            <p class="empty-card">Belum ada data OLT.</p>
                        @endforelse
                    </div>
                </section>

                <section class="panel admin-page {{ $activeAdminPage === 'users' ? 'active' : '' }}" data-admin-page="users">
                    <div class="panel-head"><div><h2>User Internal</h2><p>Tambah, edit, nonaktifkan, atau hapus akun admin dan teknisi.</p></div></div>
                    <div class="table-wrap user-list-table">
                        <table>
                            <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td><strong>{{ $user->name }}</strong></td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="badge {{ $user->role === 'super_admin' ? 'reject' : ($user->role === 'admin' ? 'done' : 'wait') }}">{{ $user->role }}</span></td>
                                        <td><span class="badge {{ $user->status === 'active' ? 'done' : 'reject' }}">{{ $user->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty-row">Belum ada user internal.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <form class="user-form" method="POST" action="{{ route('internal-users.store') }}">
                        @csrf
                        <input name="name" type="text" placeholder="Nama user" required>
                        <input name="email" type="email" placeholder="Email login" required>
                        <span class="inline-password">
                            <input name="password" type="password" placeholder="Password" required>
                            <button class="mini-password-toggle" type="button" aria-label="Lihat password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.2 0 8.6 4.5 9.7 6.2.2.3.2.8 0 1.1C20.6 14 17.2 18.5 12 18.5S3.4 14 2.3 12.3a1 1 0 0 1 0-1.1C3.4 9.5 6.8 5 12 5Zm0 11.5c3.9 0 6.7-3.1 7.7-4.7C18.7 10.2 15.9 7 12 7s-6.7 3.1-7.7 4.8c1 1.6 3.8 4.7 7.7 4.7Zm0-7.4a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4Z"/></svg>
                            </button>
                        </span>
                        <select name="role" required>
                            <option value="teknisi">Teknisi</option>
                            <option value="admin">Admin</option>
                            @if (auth()->user()?->role === 'super_admin')
                                <option value="super_admin">Super Admin</option>
                            @endif
                        </select>
                        <select name="status" required><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
                        <button class="btn" type="submit">Tambah User</button>
                    </form>
                    <div class="table-wrap user-edit-table">
                        <table>
                            <thead><tr><th>Nama</th><th>Email</th><th>Password</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <form method="POST" action="{{ route('internal-users.update', $user) }}">
                                            @csrf
                                            @method('PUT')
                                            <td><input name="name" value="{{ $user->name }}" required></td>
                                            <td><input name="email" type="email" value="{{ $user->email }}" required></td>
                                            <td>
                                                <span class="inline-password">
                                                    <input name="password" type="password" placeholder="Opsional">
                                                    <button class="mini-password-toggle" type="button" aria-label="Lihat password" aria-pressed="false">
                                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.2 0 8.6 4.5 9.7 6.2.2.3.2.8 0 1.1C20.6 14 17.2 18.5 12 18.5S3.4 14 2.3 12.3a1 1 0 0 1 0-1.1C3.4 9.5 6.8 5 12 5Zm0 11.5c3.9 0 6.7-3.1 7.7-4.7C18.7 10.2 15.9 7 12 7s-6.7 3.1-7.7 4.8c1 1.6 3.8 4.7 7.7 4.7Zm0-7.4a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4Z"/></svg>
                                                    </button>
                                                </span>
                                            </td>
                                            <td>
                                                <select name="role" required>
                                                    @if (auth()->user()?->role === 'super_admin')
                                                        <option value="super_admin" @selected($user->role === 'super_admin')>Super Admin</option>
                                                    @endif
                                                    <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                                    <option value="teknisi" @selected($user->role === 'teknisi')>Teknisi</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="status" required>
                                                    <option value="active" @selected($user->status === 'active')>Aktif</option>
                                                    <option value="inactive" @selected($user->status === 'inactive')>Nonaktif</option>
                                                </select>
                                            </td>
                                            <td class="inline-actions">
                                                <button class="btn" type="submit">Simpan</button>
                                        </form>
                                                @if (auth()->id() !== $user->id)
                                                    <form method="POST" action="{{ route('internal-users.destroy', $user) }}" onsubmit="return confirm('Hapus user ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn-danger" type="submit">Hapus</button>
                                                    </form>
                                                @endif
                                            </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>
    <script>
        const shell = document.querySelector('.internal-shell');
        const sidebarToggle = document.querySelector('.sidebar-burger');
        const pageButtons = document.querySelectorAll('[data-admin-target]');
        const pages = document.querySelectorAll('[data-admin-page]');
        const title = document.querySelector('.internal-topbar h1');

        sidebarToggle?.addEventListener('click', () => {
            const closed = shell.classList.toggle('sidebar-collapsed');
            sidebarToggle.setAttribute('aria-expanded', String(!closed));
            sidebarToggle.setAttribute('aria-label', closed ? 'Buka sidebar' : 'Tutup sidebar');
        });

        pageButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.adminTarget;
                pageButtons.forEach((item) => item.classList.toggle('active', item === button));
                pages.forEach((page) => page.classList.toggle('active', page.dataset.adminPage === target));
                title.textContent = button.textContent.trim();
                const url = new URL(window.location.href);
                url.searchParams.set('page', target);
                window.history.replaceState({}, '', url);
            });
        });

        document.querySelectorAll('.mini-password-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.closest('.inline-password')?.querySelector('input');
                if (!input) return;

                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.setAttribute('aria-pressed', String(!visible));
                button.setAttribute('aria-label', visible ? 'Lihat password' : 'Sembunyikan password');
            });
        });
    </script>
</body>
</html>
