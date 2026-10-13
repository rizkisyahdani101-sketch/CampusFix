<div class="space-y-5">
    <section class="rounded-2xl border border-teal-100 bg-teal-50 p-5">
        <p class="text-xs font-bold uppercase tracking-[.16em] text-teal-800">Panduan CampusFix</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Pusat bantuan</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Temukan langkah untuk melaporkan kendala fasilitas dan mengikuti proses perbaikannya.</p>

        <div class="mt-4 flex flex-wrap gap-2">
            @if (! auth()->check())
                <a href="{{ route('guest.issues.create') }}" class="cf-button cf-button--primary">Laporkan tanpa akun</a>
                <a href="{{ route('login') }}" class="cf-button cf-button--secondary">Masuk ke akun</a>
            @elseif (auth()->user()->role === \App\Models\User::ROLE_MEMBER)
                <a href="{{ route('issues.create') }}" class="cf-button cf-button--primary">Buat laporan</a>
                <a href="{{ route('reporter.issues.confirmation') }}" class="cf-button cf-button--secondary">Konfirmasi laporan</a>
            @elseif (auth()->user()->role === \App\Models\User::ROLE_TECHNICIAN)
                <a href="{{ route('technician.issues.index') }}" class="cf-button cf-button--primary">Buka antrean teknisi</a>
            @elseif (auth()->user()->role === \App\Models\User::ROLE_COORDINATOR)
                <a href="{{ route('coordinator.issues.review') }}" class="cf-button cf-button--primary">Verifikasi laporan</a>
                <a href="{{ route('coordinator.issues.assign') }}" class="cf-button cf-button--secondary">Atur penugasan</a>
            @elseif (auth()->user()->role === \App\Models\User::ROLE_SUPER_ADMIN)
                <a href="{{ route('admin.users.index') }}" class="cf-button cf-button--primary">Kelola pengguna</a>
                <a href="{{ route('superadmin.roles.index') }}" class="cf-button cf-button--secondary">Lihat daftar role</a>
            @else
                <a href="{{ route('admin.users.index') }}" class="cf-button cf-button--primary">Kelola pengguna</a>
            @endif
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="help-faq-title">
        <div>
            <p class="cf-eyebrow">Pertanyaan umum</p>
            <h2 id="help-faq-title" class="text-lg font-bold text-slate-900">Bagaimana CampusFix bekerja?</h2>
        </div>

        <details class="rounded-xl border border-slate-200 bg-white p-4" open>
            <summary class="cursor-pointer font-semibold text-slate-800">Bagaimana cara mengirim laporan?</summary>
            <p class="mt-2 text-sm leading-6 text-slate-600">Pilih kategori, lokasi, dan jelaskan kendala sejelas mungkin. Tambahkan detail lantai atau ruang bila diketahui. Pengunjung dapat mengirim laporan tanpa akun dan harus mengonfirmasi email terlebih dahulu.</p>
        </details>

        <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer font-semibold text-slate-800">Apa arti status laporan?</summary>
            <p class="mt-2 text-sm leading-6 text-slate-600"><strong>Menunggu verifikasi</strong> berarti koordinator belum memeriksa laporan. Setelah diverifikasi, laporan akan ditugaskan kepada tim teknisi, dikerjakan, lalu ditandai selesai. Laporan ditutup setelah pelapor mengonfirmasi perbaikan. Koordinator juga dapat menolak laporan dengan alasan.</p>
        </details>

        <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer font-semibold text-slate-800">Bagaimana memantau atau mengonfirmasi perbaikan?</summary>
            <p class="mt-2 text-sm leading-6 text-slate-600">Masuk ke menu laporan untuk melihat pembaruan. Saat teknisi menandai laporan selesai, periksa hasilnya lalu konfirmasi. Jika masalah belum teratasi, buka kembali laporan dan jelaskan alasannya.</p>
        </details>

        <details class="rounded-xl border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer font-semibold text-slate-800">Detail ruang tidak ada di pilihan lokasi?</summary>
            <p class="mt-2 text-sm leading-6 text-slate-600">Pilih fakultas, gedung, atau fasilitas yang paling dekat, lalu tulis lantai, nomor ruang, atau patokan lokasi pada kolom detail lokasi.</p>
        </details>
    </section>

    @if (auth()->check() && auth()->user()->role === \App\Models\User::ROLE_MEMBER)
        <p class="text-sm text-slate-500">Laporan Anda dapat dibuka dari <a href="{{ route('member.dashboard') }}#my-issues" class="font-semibold text-teal-800 underline">Beranda → Laporan saya</a>.</p>
    @endif
</div>
