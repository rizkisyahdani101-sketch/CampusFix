<x-portal-layout title="Semua pengguna">
    <div class="space-y-6">
        <header class="cf-page-header">
            <div>
                <p class="cf-eyebrow">Pengaturan sistem</p>
                <h1 class="cf-page-title">Semua pengguna</h1>
                <p class="mt-2 text-sm text-slate-600">Daftar akun dan role yang terdaftar di CampusFix.</p>
            </div>
        </header>

        <section class="cf-panel">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4 sm:px-5">
                <h2 class="font-semibold">Direktori pengguna</h2>
                <form method="GET" action="{{ route('superadmin.users.index') }}" class="cf-form flex w-full gap-2 sm:w-auto">
                    <label for="search" class="sr-only">Cari pengguna</label>
                    <input id="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email" class="cf-input min-w-0 sm:w-64">
                    <button class="cf-button cf-button--secondary">Cari</button>
                </form>
            </div>
            @if ($users->isEmpty())
                <p class="px-5 py-9 text-center text-sm text-slate-600">Pengguna tidak ditemukan.</p>
            @else
                <div class="cf-table-wrap">
                    <table class="cf-table">
                        <thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php($roleClass = 'cf-badge--'.str_replace('_', '-', $user->role))
                                <tr>
                                    <td class="font-semibold text-slate-800">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td><span class="cf-badge {{ $roleClass }}">{{ $user->role_label }}</span></td>
                                    <td><span class="cf-badge {{ $user->is_active ? 'cf-badge--success' : 'cf-badge--danger' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">{{ $users->withQueryString()->links() }}</div>
            @endif
        </section>
    </div>
</x-portal-layout>
