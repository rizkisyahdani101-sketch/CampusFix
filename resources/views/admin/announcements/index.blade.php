<x-portal-layout title="Informasi kampus">
    <div class="space-y-6">
        <header class="cf-page-header">
            <div>
                <p class="cf-eyebrow">Administrasi</p>
                <h1 class="cf-page-title">Informasi kampus</h1>
                <p class="mt-2 text-sm text-slate-600">Kelola pesan berjalan yang tampil di halaman CampusFix.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('admin.announcements.store') }}" class="cf-panel cf-panel-body cf-form space-y-4">
            @csrf
            <div>
                <label for="new-announcement" class="cf-label">Informasi baru</label>
                <textarea id="new-announcement" name="message" rows="3" maxlength="500" required class="cf-input" placeholder="Contoh: Pemeliharaan jaringan Wi-Fi Gedung Digital Library hari Jumat pukul 16.00.">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                    Tampilkan di running text
                </label>
                <button type="submit" class="cf-button cf-button--primary">Tambah informasi</button>
            </div>
        </form>

        <section class="cf-panel" aria-labelledby="announcement-list-title">
            <div class="border-b border-slate-100 p-4 sm:px-5">
                <h2 id="announcement-list-title" class="font-semibold">Daftar informasi</h2>
                <p class="mt-1 text-xs text-slate-500">Informasi aktif akan berjalan di bagian atas halaman untuk semua pengguna.</p>
            </div>
            @forelse ($announcements as $announcement)
                <article class="border-b border-slate-100 p-4 last:border-b-0 sm:px-5">
                    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="cf-form space-y-3">
                        @csrf
                        @method('PUT')
                        <label for="announcement-{{ $announcement->getKey() }}" class="cf-label">Pesan</label>
                        <textarea id="announcement-{{ $announcement->getKey() }}" name="message" rows="2" maxlength="500" required class="cf-input">{{ $announcement->message }}</textarea>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="is_active" value="1" @checked($announcement->is_active)>
                                Aktif di running text
                            </label>
                            <button type="submit" class="cf-button cf-button--secondary">Simpan perubahan</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cf-button cf-button--secondary text-red-700">Hapus</button>
                    </form>
                </article>
            @empty
                <p class="px-5 py-9 text-center text-sm text-slate-600">Belum ada informasi. Tambahkan pesan di atas untuk mulai menampilkan running text.</p>
            @endforelse
            @if ($announcements->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $announcements->links() }}</div>
            @endif
        </section>
    </div>
</x-portal-layout>
