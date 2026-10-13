@props([
    'title',
    'description',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f7fa">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ $title }} · {{ config('campus.brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($isRegister = request()->routeIs('register'))
@php($isRecovery = request()->routeIs('password.*'))
<body class="cf-auth-page {{ $isRegister ? 'cf-auth-page--register' : ($isRecovery ? 'cf-auth-page--recovery' : 'cf-auth-page--login') }} antialiased">
    <header class="cf-auth-header">
        <a href="{{ route('login') }}" class="cf-auth-brand">
            <x-campusfix-mark />
            CampusFix
        </a>
        <nav aria-label="Akun">
            <a href="{{ route('help') }}" class="cf-auth-help">Pusat bantuan</a>
            <a href="{{ $isRegister ? route('login') : route('register') }}" class="cf-auth-header-action">{{ $isRegister ? 'Masuk' : 'Daftar akun' }}</a>
        </nav>
    </header>

    <x-campus-announcement-ticker />

    <main class="cf-auth-layout">
        <section class="cf-auth-hero" aria-labelledby="campusfix-headline">
            <span class="cf-auth-kicker">◉&nbsp; {{ config('campus.name') }} ({{ config('campus.short_name') }})</span>
            <h1 id="campusfix-headline">{{ $isRegister ? 'Satu akun. Banyak perubahan baik.' : 'Kampus nyaman, mulai dari kita.' }}</h1>
            <p>{{ $isRegister ? 'Jadi bagian dari kampus yang lebih peduli. Sampaikan kendala fasilitas dan bantu wujudkan ruang belajar yang nyaman untuk semua.' : 'Laporkan kendala fasilitas, pantau perbaikannya, dan bantu kampus jadi lebih baik. Suaramu membawa perubahan.' }}</p>
            <div class="cf-auth-campus-photo" role="img" aria-label="Gedung Perpustakaan Digital Universitas Negeri Medan"></div>
        </section>

        <section class="cf-auth-card" aria-labelledby="auth-title">
            <a href="{{ route('login') }}" class="cf-brand"><x-campusfix-mark />CampusFix</a>
            <div class="cf-auth-heading">
                <span class="cf-eyebrow">{{ $isRegister ? 'Mulai kontribusimu' : ($isRecovery ? 'Pemulihan akun' : 'Selamat datang kembali') }}</span>
                <h2 id="auth-title">
                    <span class="cf-desktop-copy">{{ $title }}</span>
                    <span class="cf-mobile-copy">{{ $isRegister ? 'Yuk, buat akunmu' : ($isRecovery ? $title : 'Selamat datang kembali') }}</span>
                </h2>
                <p>{{ $description }}</p>
            </div>
            <div class="cf-auth-tenant" aria-label="Email kampus aktif">
                <span aria-hidden="true">♙</span>
                <div><strong>{{ config('campus.name') }} ({{ config('campus.short_name') }})</strong><span>Mahasiswa dapat menggunakan email &#64;{{ config('campus.student_email_domain') }}.</span></div>
            </div>
            {{ $slot }}
        </section>
    </main>
</body>
</html>
