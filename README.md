# Mikhmon RouterOS 7

Mikhmon (MikroTik Hotspot Monitor) untuk RouterOS v7 — dikemas dalam Docker.

## Fitur

- **No database** — semua data disimpan dalam file konfigurasi
- **Web-based** — dikelola lewat browser
- **Fokus hotspot & voucher** — tidak termasuk manajemen PPP (PPPoE)
- **Production-ready** — berbasis `php:8.3-apache`

## Persyaratan

- Docker (v20+)
- Docker Compose (opsional)

## Cara Menjalankan

### Menggunakan Docker

```bash
# 1. Build image
docker build -t mikhmon-ros7 .

# 2. Jalankan container (port 8181 di host -> port 80 di container)
docker run -d --name mikhmon \
  -p 8181:80 \
  mikhmon-ros7
```

### Menggunakan Docker Compose

```bash
docker compose up -d --build
```

Ubah port host jika perlu (bisa lewat `.env`):

```bash
# .env
HOST_PORT=8181
```

Konfigurasi compose (`docker-compose.yml`):
- **Volume `mikhmon-data`** — menyimpan konfigurasi sesi, pengaturan, logo & template voucher agar tidak hilang saat container di-recreate
- **Healthcheck** — menandai container `healthy` jika web server merespons
- **Restart otomatis** — `restart: unless-stopped`
- **Timezone** — default `Asia/Jakarta`, bisa diubah lewat env `TZ`

## Akses

Buka browser: `http://localhost:8181`

- **Username:** `mikhmon`
- **Password:** `1234`

> **Ganti password default setelah login!** Ada di menu `Admin` -> pengaturan.

## Konfigurasi Router

Mikhmon terhubung ke RouterOS via **API RouterOS** (default port 8728).

1. Aktifkan API di router:
   ```
   /ip service set api disabled=no
   /user set admin password=<password-baru>

2. Di Mikhmon, buat *session* baru dan isi alamat IP router, username, dan password.

> Pastikan port 8728 di router bisa diakses dari container.

### Port API Kustom (VPN / Port Forwarding)

Mikhmon mendukung penulisan port langsung di kolom *IP MikroTik*, format `host:port`.

Contoh: RouterOS diakses melalui port forwarding L2TP VPN:

```
sg-01.drpnet.my.id:51244
```

- Koneksi API akan menuju `sg-01.drpnet.my.id:51244` (bukan 8728)
- Fitur *ping test* dan koneksi sesi sama-sama memakai port tersebut
- Jika tanpa port, otomatis memakai default 8728
- Mendukung IPv6 dengan format `[::1]:8729`

## Arsitektur

```
src/                   # Kode sumber aplikasi (di-copy ke image)
Dockerfile             # Build image berbasis Apache + mod_php
docker-compose.yml     # Konfigurasi compose
docker-entrypoint.sh   # Entrypoint: memastikan izin tulis folder aplikasi
```

## Catatan Teknis

- Berbasis `php:8.3-apache` (Apache + mod_php) — bukan PHP development server, sehingga siap dipakai di produksi
- Ekstensi `sockets` diaktifkan untuk koneksi API RouterOS
- Tidak memerlukan `.htaccess` / mod_rewrite; Mikhmon murni menggunakan query string
- Untuk Nginx + PHP-FPM bisa dipakai, tapi tidak wajib untuk beban Mikhmon yang ringan
