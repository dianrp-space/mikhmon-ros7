# Mikhmon RouterOS 7

Mikhmon (MikroTik Hotspot Monitor) untuk RouterOS v7 — dikemas dalam Docker.

## Fitur

- **SQLite untuk report penjualan** — data report disimpan di `data/mikhmon.db`, bukan menumpuk sebagai script di router
- **Auto-sync report** — cron menjalankan `syncreport.php` setiap menit: mengimpor script penjualan dari router ke SQLite, lalu menghapusnya dari router
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

### Deploy via Registry (GHCR) — Tanpa Clone Repo

Image sudah otomatis di-build & di-push ke **GitHub Container Registry** (`ghcr.io/dianrp-space/mikhmon-ros7:latest`) setiap ada push ke `main` (lewat GitHub Actions).

Untuk produksi di VPS, tidak perlu clone repo — cukup satu file `compose.prod.yml`:

```yaml
services:
  mikhmon:
    image: ghcr.io/dianrp-space/mikhmon-ros7:latest
    container_name: mikhmon
    ports:
      - "${HOST_PORT:-8181}:80"
    volumes:
      - mikhmon-data:/var/www/html
    environment:
      TZ: Asia/Jakarta
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "php", "-r", "$$sock=@fsockopen('localhost',80);exit($$sock?0:1);"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 10s

volumes:
  mikhmon-data:
```

Karena repo ini **publik**, image GHCR-nya publik juga — tanpa perlu login:

```bash
docker compose -f compose.prod.yml pull
docker compose -f compose.prod.yml up -d
```

Update di kemudian hari cukup:

```bash
docker compose -f compose.prod.yml pull && docker compose -f compose.prod.yml up -d
```

Data (sessions, pengaturan, logo, template, database report) tersimpan di volume `mikhmon-data`, sehingga aman saat image di-update.

## Akses

Buka browser: `http://localhost:8181`

- **Username:** `mikhmon`
- **Password:** `1234`

> **Ganti password default setelah login!** Ada di menu `Admin` -> pengaturan.

## Deploy Alternatif (Tanpa Docker)

Ingin menjalankan langsung di nginx + PHP-FPM tanpa Docker (mis. VPS aaPanel)?
Lihat [docs/deploy-nginx-aapanel.md](docs/deploy-nginx-aapanel.md).

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

## Report Penjualan (SQLite)

Secara default Mikhmon menyimpan data penjualan sebagai *script* di router — ini
menumpuk seiring waktu. Fork ini mengubahnya:

1. **On-login tetap membuat script** di router (format nama `tanggal-|-jam-|-user-|-harga-|-address-|-mac-|-validity-|-profile-|-comment`, comment `mikhmon`)
2. **Cron tiap menit** menjalankan `process/syncreport.php` untuk tiap sesi:
   - Membaca semua script ber-comment `mikhmon` dari router
   - Mengimpor ke tabel `sales` di `data/mikhmon.db` (duplikat diabaikan via UNIQUE)
   - Menghapus script tersebut dari router (tidak menumpuk)
3. **Halaman Report** (`selling`, `print`, `livereport`, `removereport`) membaca & menghapus data dari SQLite — tidak lagi lewat API router

Log sinkronisasi ada di `data/sync.log` (satu baris per sesi per menit), contoh:

```
[2026-07-31 23:53:13] [Remote-Batam] scripts=0 imported=0 removed=0
[2026-07-31 23:54:13] [Remote-Batam] scripts=1 imported=1 removed=1
```

Jika ada sesi yang tidak bisa dihubungi, log mencatat `Connection failed` dan sesi
lain tetap diproses.

## Arsitektur

```
src/                    # Kode sumber aplikasi (di-copy ke /opt/mikhmon di image)
src/lib/db.php          # Helper SQLite (tabel sales + fungsi baca/hapus/upsert)
src/process/syncreport.php  # Cron: sync report router -> SQLite -> hapus script
src/report/*.php        # Halaman report membaca data dari SQLite
Dockerfile              # Build image berbasis Apache + mod_php
docker-compose.yml      # Konfigurasi compose (build lokal)
compose.prod.yml        # Konfigurasi compose produksi (pull dari GHCR, tanpa build)
docker-entrypoint.sh    # Sync kode ke volume + start cron, lalu jalankan CMD
crontab                 # Jadwal cron sync report (tiap menit, user www-data)
.github/workflows/docker-build.yml  # CI: build & push image ke GHCR tiap push ke main
```

## Catatan Teknis

- Berbasis `php:8.3-apache` (Apache + mod_php) — bukan PHP development server, sehingga siap dipakai di produksi
- Ekstensi `sockets` (koneksi API RouterOS) dan `pdo_sqlite` / `sqlite3` (report SQLite) diaktifkan
- Kode sumber di-copy ke `/opt/mikhmon` di image; saat container start, `docker-entrypoint.sh` meng-`rsync` kode ke volume `/var/www/html` tapi **menjaga** file runtime (`include/config.php`, `lang`, `theme`, `quickbt`, `img/`, `voucher/*.php`, `data/`) — sehingga pengaturan sesi & template tidak hilang saat image di-rebuild
- File runtime (`include/config.php`, `lang.php`, `theme.php`, `quickbt.php`) **tidak di-track git** — disimpan sebagai `*.example.php`. Docker membuatnya dari contoh saat volume kosong; di deploy nginx salin manual (`cp include/*.example.php include/*.php`). Ini memastikan `git pull` tidak pernah menimpa login admin & session router
- Cron dijalankan dari entrypoint sebagai daemon (`/usr/sbin/cron`), jadwal dari `/etc/cron.d/mikhmon`
- Tidak memerlukan `.htaccess` / mod_rewrite; Mikhmon murni menggunakan query string
- Untuk Nginx + PHP-FPM bisa dipakai, tapi tidak wajib untuk beban Mikhmon yang ringan
