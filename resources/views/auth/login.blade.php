<x-auth-layout title="Masuk ke CampusFix" description="Lanjutkan kontribusimu untuk kampus yang lebih nyaman.">
    @if (session('status'))
        <div role="status" class="cf-alert cf-alert--success mb-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="cf-alert cf-alert--error mb-5">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="cf-auth-form cf-form">
        @csrf

        <div>
            <label for="email" class="cf-label">Email kampus</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama&#64;{{ config('campus.student_email_domain') }}" class="cf-input">
        </div>

        <div>
            <label for="password" class="cf-label">Kata sandi</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" class="cf-input">
        </div>

        <div class="flex items-center justify-between gap-3 text-xs text-slate-600">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="size-4 rounded border-slate-300">
                Ingat saya
            </label>
            <a href="{{ route('password.request') }}" class="font-semibold text-teal-800 hover:underline">Lupa kata sandi?</a>
        </div>

        <button type="submit" class="cf-button cf-button--primary w-full">Masuk <span aria-hidden="true">→</span></button>
    </form>

    <div class="cf-auth-footer space-y-3">
        <p>Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
        <a class="cf-auth-guest-link" href="{{ route('guest.issues.create') }}">Ingin melapor tanpa akun?</a>
    </div>
</x-auth-layout>
