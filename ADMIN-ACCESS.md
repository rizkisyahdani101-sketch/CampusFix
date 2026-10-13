# Cara Mengakses Admin CampusFix

## 0. Prasyarat: MySQL harus berjalan

Kalau `mysqld` mati, halaman login tetap terbuka (HTTP 200) tetapi **submit login
gagal dengan error 500** (`SQLSTATE[HY000] [2002] No connection could be made ...`
/ `actively refused`) — bukan pesan "password salah". Jadi cek ini dulu sebelum
menyalahkan kredensial.

```sh
# cek (harus menjawab: mysqld is alive)
"C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqladmin.exe" -u root ping

# start manual kalau service Laragon mati
"C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqld.exe" \
  --defaults-file="C:/laragon/bin/mysql/mysql-8.0.30-winx64/my.ini" --console
# atau klik "Start All" di Laragon
```

## 1. Login super admin
- URL     : `http://127.0.0.1:8899/login`
- Email   : `admin@campusfix.test`
- Password: `admin12345milik123`

Kredensial di atas persis sama dengan `.env`
(`CAMPUSFIX_SUPER_ADMIN_NAME|EMAIL|PASSWORD`) dan akun ini ber-role
**`super_admin`** dengan `is_active = 1`, jadi punya akses ke semua halaman admin
+ superadmin. `SuperAdminSeeder` hanya **membuat** akun bila belum ada
(`firstOrCreate`) — seeder **tidak** menimpa password akun yang sudah ada.

## Halaman yang bisa diakses setelah login
| Halaman                       | URL                      | Status  |
|-------------------------------|--------------------------|---------|
| Dashboard Super Admin         | `/superadmin/dashboard`  | HTTP 200 |
| Kelola Pengguna (admin)       | `/admin/users`           | HTTP 200 |
| Tambah Pengguna               | `/admin/users/create`    | HTTP 200 |
| Kelola Role                   | `/superadmin/roles`      | HTTP 200 |
| Kelola Pengumuman Kampus      | `/admin/announcements`   | HTTP 200 |
| Profil saya                   | `/account`               | HTTP 200 |

Login super admin → `302` ke `/superadmin/dashboard`. Login member
(`member@gmail.com`) → `302` ke `/member/dashboard`, dan akses manual ke
`/admin/users` ditolak dengan **HTTP 403** (kontrol akses role tetap utuh).

## Teknisi / Koordinator
Dibuat lewat halaman "Kelola Pengguna" (`/admin/users/create`) — bukan lewat
registrasi publik. `php artisan db:seed --class=StaffUserSeeder --force` juga
membuat akun staff demo berikut (idempotent, aman dijalankan ulang):

| Role        | Email                            | Password                | Team        |
|-------------|----------------------------------|-------------------------|-------------|
| coordinator | `koordinator@campusfix.test`     | `koordinator123milik12` | —           |
| technician  | `teknisi.facility@campusfix.test`| `teknisi123milik12`     | Facility    |
| technician  | `teknisi.it@campusfix.test`      | `teknisi123milik12`     | IT Support  |

(Terterifikasi: ketiga password cocok dengan hash di database dan login →
dashboard HTTP 200; baris `team_user` sinkron.)

Registrasi publik (`/register`) selalu menghasilkan akun `member`.

## Profil & Ganti Password (semua role)
Link **“Profil saya”** ada di sidebar portal setelah login → `/account`:
- Data akun (nama, email, NIM, role, dll.) **read-only**
- Form **Ubah Kata Sandi**: verifikasi sandi lama, sandi baru min. 8 karakter,
  lalu semua perangkat lain otomatis keluar

## Kalau kredensial admin tidak cocok lagi (reset password)
Penyebab paling umum: password pernah diganti lewat menu `/account` (mis. saat
smoke test), sehingga database tidak lagi sinkron dengan `.env` dan dokumen ini.
Jalankan salah satu:

```sh
# 1) Sinkronkan ulang dari .env (mengganti password akun yang sudah ada)
cd C:\laragon\www\campusfix
php artisan tinker --execute="\$u = App\Models\User::where('email', 'admin@campusfix.test')->firstOrFail(); \$u->forceFill(['role' => App\Models\User::ROLE_SUPER_ADMIN, 'is_active' => true, 'password' => Illuminate\Support\Facades\Hash::make((string) env('CAMPUSFIX_SUPER_ADMIN_PASSWORD'))])->save(); echo 'password diset ulang';"

# 2) Atau ubah langsung di database lewat halaman /account setelah login
```

Setelah reset, login ulang di `http://127.0.0.1:8899/login`.

> Catatan: karena `.env` memakai `MAIL_MAILER=log`, link dari menu
> **“Lupa kata sandi?”** tidak dikirim ke email — isinya hanya tertulis di
> `storage/logs/laravel.log`. Jadi prosedur di atas (`tinker`) adalah cara reset
> yang paling cepat untuk akun admin lokal.

## Perintah restart server lokal
```sh
cd C:\laragon\www\campusfix
php artisan serve --host=127.0.0.1 --port=8899
# atau full dev (server + queue worker + pail + vite):
composer run dev
```
