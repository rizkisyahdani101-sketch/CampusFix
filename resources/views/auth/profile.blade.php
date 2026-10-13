<x-portal-layout title="Profil Saya">
    @php($user = auth()->user())

    <div class="max-w-3xl space-y-6">

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="profile-photo">
            <h2 id="profile-photo" class="text-lg font-bold text-teal-900">Foto Profil</h2>
            <p class="mt-1 text-sm text-slate-500">Unggah foto JPG, PNG, atau WebP berukuran maksimal 2 MB. Foto ini tampil di navbar portal, menu profil, dan daftar pengguna.</p>

            <div class="mt-5 flex flex-wrap items-start gap-6">
                <x-user-avatar :user="$user" class="cf-profile-avatar" />

                <form method="POST" action="{{ route('account.avatar') }}" enctype="multipart/form-data" class="min-w-64 flex-1 space-y-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="avatar" class="cf-label">{{ $user->avatar_url ? 'Ganti dengan foto baru' : 'Pilih foto profil' }}</label>
                        <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" required class="cf-input">
                        <p class="mt-1 text-xs text-slate-500">Format didukung: JPG, PNG, WebP. Maksimal 2 MB.</p>
                        @error('avatar') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="cf-button cf-button--primary">Simpan foto profil</button>
                </form>

                @if ($user->avatar_url)
                    <form method="POST" action="{{ route('account.avatar.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cf-button cf-button--danger">Hapus foto</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="profile-data">
            <h2 id="profile-data" class="text-lg font-bold text-teal-900">Data Akun</h2>
            <p class="mt-1 text-sm text-slate-500">Nama, email, NIM, dan role dikelola oleh koordinator/admin. Kamu dapat mengubah foto profil dan kata sandi sendiri.</p>

            <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $user->name }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $user->email }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIM / ID</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $user->student_id ?? '—' }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</dt>
                    <dd class="mt-1 font-semibold text-slate-800">{{ $user->role_label }}</dd>
                </div>
                @if (filled($user->department))
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jurusan</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $user->department }}</dd>
                    </div>
                @endif
                @if (filled($user->phone))
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telepon</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $user->phone }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="change-password">
            <h2 id="change-password" class="text-lg font-bold text-teal-900">Ubah Kata Sandi</h2>
            <p class="mt-1 text-sm text-slate-500">Setelah diganti, semua perangkat lain yang aktif akan otomatis keluar.</p>

            @if ($errors->hasAny(['current_password', 'password']))
                <div role="alert" class="cf-alert cf-alert--error mt-4">{{ $errors->first('current_password') ?: $errors->first('password') }}</div>
            @endif

            <form method="POST" action="{{ route('account.password') }}" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="cf-label">Kata sandi saat ini</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="cf-input @error('current_password') border-rose-400 @enderror">
                </div>

                <div>
                    <label for="password" class="cf-label">Kata sandi baru</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="cf-input @error('password') border-rose-400 @enderror">
                </div>

                <div>
                    <label for="password_confirmation" class="cf-label">Konfirmasi kata sandi baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="cf-input">
                </div>

                <button type="submit" class="cf-button cf-button--primary">Simpan kata sandi</button>
            </form>
        </section>

    </div>
</x-portal-layout>
