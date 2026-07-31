# Deploy Mikhmon di nginx + aaPanel (Tanpa Docker)

Mikhmon adalah aplikasi PHP murni — tanpa framework, tanpa mod_rewrite, tanpa
dependensi sistem khusus. Jadi bisa dijalankan langsung di nginx + PHP-FPM
lewat aaPanel. Lebih ringan daripada image Docker (~500MB).

## Persyaratan

- VPS dengan aaPanel (CentOS / Ubuntu / Debian)
- nginx sudah terpasang (default aaPanel)
- PHP 8.0+ dengan ekstensi:
  - `sockets` — koneksi API RouterOS
  - `pdo_sqlite` — report penjualan (database SQLite)
  - `sqlite3`
- User sistem untuk cron (pakai `www` bawaan aaPanel)

## Langkah Instalasi

### 1. Install & konfigurasi PHP di aaPanel

1. aaPanel → **App Store** → **PHP** → install PHP 8.x
2. Buka **Setting** PHP yang terpasang → **Install Extensions**
   → centang `sockets`, `pdo_sqlite`, `sqlite3` → Install
3. (Opsional) Restart PHP-FPM lewat tombol **Restart**

### 2. Buat site

1. aaPanel → **Websites** → **Add Site**
2. Domain: isi domain atau IP (mis. `mikhmon.drpnet.my.id`)
3. PHP version: pilih PHP 8.x yang tadi
4. **Create**

Catatan lokasi default: `/www/wwwroot/<domain>` (contoh: `/www/wwwroot/mikhmon`).

### 3. Upload kode

Copy isi folder `src/` dari repo ini ke webroot:

```bash
# contoh: repo diclone di /tmp/mikhmon
rsync -a /tmp/mikhmon/src/ /www/wwwroot/mikhmon/
```

Atau via aaPanel **File** → upload hasil `git archive` / zip isi `src/`.

> Gunakan **isi** folder `src/`, bukan folder `src/`-nya — supaya `index.php`
> langsung berada di webroot, bukan di `/www/wwwroot/mikhmon/src/`.

### 4. Set permission

```bash
chown -R www:www /www/wwwroot/mikhmon
chmod -R 755 /www/wwwroot/mikhmon
chmod 775 /www/wwwroot/mikhmon/data   # folder DB SQLite harus writable
```

### 5. Setup cron sync report

Di aaPanel: **Cron** → **Add Cron Task** → Script (Shell).

- Type: **Shell Script**
- Name: `mikhmon sync report`
- Period: **Every 1 minute** (`*/1 * * * *`)

Script (ganti `<ver>` dengan versi PHP aaPanel, mis. `81`, dan path webroot):

```bash
/www/server/php/<ver>/bin/php /www/wwwroot/mikhmon/process/syncreport.php >> /www/wwwroot/mikhmon/data/sync.log 2>&1
```

Contoh lengkap dengan PHP 8.1:

```bash
/www/server/php/81/bin/php /www/wwwroot/mikhmon/process/syncreport.php >> /www/wwwroot/mikhmon/data/sync.log 2>&1
```

### 6. Akses

Buka `http://<domain>/` di browser.

- **Username:** `mikhmon`
- **Password:** `1234`

> **Ganti password default setelah login!** Menu `Admin` → pengaturan.

## Perbedaan vs Deploy Docker

| Aspek | Docker | nginx/aaPanel |
| --- | --- | --- |
| Kode | disync dari image ke volume, file runtime dipertahankan | copy sekali, edit langsung di webroot |
| Update | `git push` → VPS `pull && up -d` | tarik kode baru lalu overwrite webroot (file runtime: `include/config.php`, `lang`, `theme`, `quickbt`, `img/`, `voucher/*.php`, `data/` dipertahankan) |
| DB SQLite | di volume | di `/www/wwwroot/mikhmon/data/mikhmon.db` (wajib writable) |
| Cron | bawaan image | cron aaPanel |

## Troubleshooting

**Halaman report kosong / error PDO sqlite tidak ditemukan**
→ Ekstensi `pdo_sqlite` belum aktif. Cek lewat `phpinfo()` atau:

```bash
/www/server/php/<ver>/bin/php -m | grep -i sqlite
```

**Sesi tidak bisa konek ke router**
→ Pastikan ekstensi `sockets` aktif dan port API (8728) di router bisa diakses
dari VPS.

**`data/sync.log` tidak tumbuh**
→ Cek cron aaPanel berjalan (log cron aaPanel), dan pastikan path php serta
webroot benar.

**Error "permission denied" saat save settings**
→ Pastikan owner seluruh webroot `www:www` dan folder `data/` writable.
