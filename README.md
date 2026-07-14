# BozonkNet

Sistem Informasi Geografis berbasis Laravel untuk memeriksa cakupan layanan internet BozonkNet di Kecamatan Bojonggede.

## Fitur MVP

- Peta interaktif Leaflet dan OpenStreetMap.
- Radius area tersedia dan area perluasan.
- Marker desa serta titik hub/ODP.
- Pengecekan lokasi melalui klik peta atau pilihan desa.
- Perhitungan jarak lokasi dari hub utama.
- Form pengajuan area yang tersimpan ke MySQL.

## Teknologi

- Laravel 13 dan PHP 8.5
- Laravel Sail / Docker Compose
- MySQL 8.4
- Leaflet dan OpenStreetMap

## Menjalankan proyek

Pastikan Docker Desktop, PHP, dan Composer tersedia.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
docker compose up -d --build
docker compose exec laravel.test php artisan migrate --seed
```

Buka `http://localhost` pada browser.

Untuk menghentikan container tanpa menghapus database:

```powershell
docker compose down
```

## Pengujian

```powershell
docker compose exec laravel.test php artisan test
```

## Catatan pengembangan Windows

Performa bind mount Docker dapat melambat jika proyek berada di folder sinkronisasi OneDrive. Untuk proyek baru, penyimpanan di filesystem WSL2 disarankan.
