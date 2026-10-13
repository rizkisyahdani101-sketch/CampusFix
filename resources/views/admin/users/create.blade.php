<x-portal-layout title="Tambah pengguna">
    <div class="mx-auto max-w-3xl space-y-6">
        <header>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-teal-800 hover:underline">← Kembali ke pengguna</a>
            <p class="cf-eyebrow mt-5">Administrasi</p>
            <h1 class="cf-page-title">Tambah pengguna</h1>
            <p class="mt-2 text-sm text-slate-600">Buat akun kerja dan tentukan peran yang sesuai.</p>
        </header>

        @if ($errors->any())
            <div role="alert" class="cf-alert cf-alert--error">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="cf-panel cf-panel-body cf-form space-y-5" data-admin-user-form>
            @csrf
            <div>
                <label for="name" class="cf-label">Nama</label>
                <input type="text" id="name" name="name" required autocomplete="name" value="{{ old('name') }}" class="cf-input">
            </div>
            <div>
                <label for="email" class="cf-label">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email" value="{{ old('email') }}" class="cf-input">
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="cf-label">Kata sandi</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" class="cf-input">
                </div>
                <div>
                    <label for="password_confirmation" class="cf-label">Konfirmasi kata sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="cf-input">
                </div>
            </div>
            <div>
                <label for="role" class="cf-label">Role</label>
                <select id="role" name="role" required class="cf-input" data-admin-user-role>
                    <option value="">Pilih role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role') === $role)>{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                    @endforeach
                </select>
            </div>
            <div id="technician-team-fields" data-technician-team-section @if (old('role') !== \App\Models\User::ROLE_TECHNICIAN) hidden @endif>
                <label for="team_ids" class="cf-label">Tim teknisi <span class="font-normal text-slate-500">(wajib untuk teknisi)</span></label>
                @if ($teams->isNotEmpty())
                    <select id="team_ids" name="team_ids[]" multiple size="{{ max(2, min(5, $teams->count())) }}" class="cf-input" @if (old('role') !== \App\Models\User::ROLE_TECHNICIAN) disabled @endif @required(old('role') === \App\Models\User::ROLE_TECHNICIAN)>
                        @foreach ($teams as $team)
                            <option value="{{ $team->getKey() }}" @selected(in_array($team->getKey(), old('team_ids', [])))>{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @error('team_ids')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    @error('team_ids.*')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-slate-500">Pilih satu atau lebih tim yang menjadi cakupan teknisi.</p>
                @else
                    <p role="status" class="cf-alert cf-alert--warning text-sm">Belum ada tim. Jalankan <code>php artisan db:seed --class=TeamSeeder</code> untuk menambahkan tim Facility dan IT Support.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                <button type="submit" class="cf-button cf-button--primary">Simpan pengguna</button>
                <a href="{{ route('admin.users.index') }}" class="cf-button cf-button--secondary">Batal</a>
            </div>
        </form>
    </div>
</x-portal-layout>
