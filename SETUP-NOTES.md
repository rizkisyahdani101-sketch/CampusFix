# CampusFix — Status Pengerjaan

## Lokasi proyek
- Proyek dipindah ke `C:\laragon\www\campusfix` (folder `Downloads` membuat `is_writable()` PHP
  selalu `false`, sehingga semua perintah `artisan` gagal: "bootstrap/cache must be present and writable").

## Yang diperbaiki
1. **Environment (tanpa mengubah logika aplikasi)**
   - `bootstrap/cache/packages.php` & `services.php` dibuat ulang fresh (dir lama bawa atribut
     bermasalah dari Downloads; `is_writable()` salah negatif).
   - `php.ini` Laragon (PHP 8.5.11): mengaktifkan `extension=pdo_sqlite` & `extension=sqlite3`
     agar test suite bisa jalan.
   - `npm install` + `npm run build` → manifest Vite (`public/build/manifest.json`) dibuat;
     akar penyebab 8 test gagal (ViteManifestNotFoundException).
   - `composer install` sudah lengkap, `php artisan key:generate` diatur.

2. **Database (Laragon MySQL 8.0.30)**
   - Database `campusfix` dibuat (utf8mb4_unicode_ci).
   - `.env` di-project sudah menunjuk MySQL Laragon: host 127.0.0.1, port 3306, root/blank.
   - `php artisan migrate:fresh --seed` berhasil → 25 tabel.
   - Isi seed: 8 kategori, 25 lokasi, 2 team (Facility, IT Support), 1 pengumuman kampus.
   - `users = 0` memang sesuai desain: `SuperAdminSeeder` hanya membuat akun bila
     `CAMPUSFIX_SUPER_ADMIN_*` diisi di `.env`. Teknisi dibuat dari halaman admin.

3. **Logika workflow (tidak diubah karena audit lolos)**
   - `App\Services\IssueWorkflow` — transisi status, lock, history, merge, close/reopen, guest flow: konsisten.
   - `App\Enums\IssueStatus::TRANSITIONS` — mesin status legal & lengkap.
   - `App\Policies\IssuePolicy` — otorisasi per role + status, benar.
   - `php artisan test` → **45 passed, 160 assertions** (semua hijau; sebelumnya 8 gagal hanya karena Vite).

## Cara menjalankan
```sh
cd C:\laragon\www\campusfix
composer run dev        # server + queue worker + pail + vite
# atau
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
```
Server smoke test sudah dilakukan (`/login`, `/register`, `/lapor-tanpa-akun` → HTTP 200).
Saat ini `php artisan serve --port=8899` masih berjalan di background.

## Verifikasi database cepat
```sh
C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe -u root campusfix -e "SHOW TABLES;"
```
