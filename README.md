# CampusFix

CampusFix mengubah laporan fasilitas kampus menjadi alur kerja yang dapat dilacak: mahasiswa melapor, coordinator memverifikasi dan memberi prioritas, technician menerima serta mengerjakan tugas, lalu pelapor mengonfirmasi hasilnya. Setiap perubahan status masuk ke riwayat, dan pihak terkait menerima notifikasi.

## Teknologi

- PHP 8.3+
- Laravel 12, Blade, Livewire 4, Alpine.js, Tailwind CSS
- MySQL 8 dan Eloquent ORM
- Laravel Policies, Notifications, Scheduler, dan database queue
- PHPUnit

## Fitur MVP

- Register dan login; registrasi publik selalu membuat akun `member`.
- Lima role: `member`, `technician`, `coordinator`, `admin`, `super_admin`.
- Team `Facility` dan `IT Support`; teknisi merupakan anggota team, bukan role terpisah.
- Laporan fasilitas dengan kategori, hierarki lokasi, foto, dan penanda keselamatan/dampak kelas.
- Deteksi laporan serupa berdasarkan judul, kategori, dan lokasi; member dapat menandai “Saya juga terdampak”.
- Verifikasi, penolakan, prioritas, assignment, accept/start, update progress, hold, resolution, konfirmasi, reopen, dan merge duplikat.
- Status history append-only dan notifikasi database yang langsung tersimpan tanpa memerlukan queue worker.
- Dashboard berisi ringkasan status, issue terbaru, notifikasi, dan rata-rata feedback.

Status issue hanya diubah oleh `App\Services\IssueWorkflow`; komponen Livewire tidak menetapkan status utama secara langsung.

## Menjalankan secara lokal

1. Buat database MySQL 8 bernama `campusfix`, lalu salin `.env.example` ke `.env`.
2. Atur `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
3. (Opsional) Atur `CAMPUSFIX_SUPER_ADMIN_NAME`, `CAMPUSFIX_SUPER_ADMIN_EMAIL`, dan password minimal 12 karakter untuk membuat akun super admin saat seeding. Jika kredensial tidak diisi, seeder tidak membuat akun admin.
4. Jalankan:

   ```sh
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm install
   npm run build
   ```

5. Jalankan aplikasi dan worker database queue:

   ```sh
   composer run dev
   ```

   Perintah development menjalankan server Laravel, worker queue, log viewer, dan Vite.

Daftarkan teknisi melalui halaman pengguna admin dan pilih team mereka. Issue coordinator dan antrean teknisi tersedia di dashboard masing-masing role.

## Pengujian

Jalankan suite PHPUnit dengan `php artisan test` setelah dependensi dan database tersedia.
