@auth
    @php($currentUser = auth()->user())
    <header class="cf-topbar">
        <div class="cf-topbar-inner">
            <a href="{{ route('dashboard') }}" class="cf-brand"><x-campusfix-mark />CampusFix</a>

            <nav class="cf-nav" aria-label="Navigasi utama">
                <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard', '*dashboard')) aria-current="page" @endif>Dashboard</a>

                @if ($currentUser->role === \App\Models\User::ROLE_MEMBER)
                    <a href="{{ route('issues.create') }}" @if (request()->routeIs('issues.create')) aria-current="page" @endif>Buat laporan</a>
                    <a href="{{ route('reporter.issues.confirmation') }}" @if (request()->routeIs('reporter.*')) aria-current="page" @endif>Konfirmasi</a>
                @elseif ($currentUser->role === \App\Models\User::ROLE_TECHNICIAN)
                    <a href="{{ route('technician.issues.index') }}" @if (request()->routeIs('technician.issues.*')) aria-current="page" @endif>Antrean teknisi</a>
                @elseif ($currentUser->role === \App\Models\User::ROLE_COORDINATOR)
                    <a href="{{ route('coordinator.issues.review') }}" @if (request()->routeIs('coordinator.issues.review')) aria-current="page" @endif>Verifikasi</a>
                    <a href="{{ route('coordinator.issues.assign') }}" @if (request()->routeIs('coordinator.issues.assign')) aria-current="page" @endif>Penugasan</a>
                @elseif (in_array($currentUser->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true))
                    <a href="{{ route('admin.users.index') }}" @if (request()->routeIs('admin.users.*')) aria-current="page" @endif>Pengguna</a>
                    @if ($currentUser->role === \App\Models\User::ROLE_SUPER_ADMIN)
                        <a href="{{ route('superadmin.users.index') }}" @if (request()->routeIs('superadmin.users.*')) aria-current="page" @endif>Semua pengguna</a>
                        <a href="{{ route('superadmin.roles.index') }}" @if (request()->routeIs('superadmin.roles.*')) aria-current="page" @endif>Role</a>
                    @endif
                @endif
            </nav>

            <div class="cf-userbar">
                <div class="cf-user-meta">
                    <strong>{{ $currentUser->name }}</strong>
                    <span>{{ $currentUser->role_label }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="cf-button cf-button--secondary">Keluar</button>
                </form>
            </div>
        </div>
    </header>
@endauth
