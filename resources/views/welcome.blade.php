<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f8f7">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ config('campus.brand') }} · Fasilitas kampus yang lebih baik</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="cf-app antialiased">
    <header class="cf-topbar">
        <div class="cf-topbar-inner">
            <a href="{{ route('login') }}" class="cf-brand"><x-campusfix-mark />CampusFix</a>
            <nav class="flex items-center gap-2" aria-label="Akun">
                <a href="{{ route('login') }}" class="cf-button cf-button--secondary">Masuk</a>
                <a href="{{ route('guest.issues.create') }}" class="cf-button cf-button--primary">Lapor masalah</a>
            </nav>
        </div>
    </header>

    <x-campus-announcement-ticker />

    <main class="cf-page">
        <section class="grid items-center gap-8 lg:grid-cols-[1.05fr_.95fr] lg:gap-14">
            <div class="space-y-6 py-8 lg:py-16">
                <span class="cf-auth-kicker">✦ {{ config('campus.name') }} ({{ config('campus.short_name') }})</span>
                <h1 class="max-w-3xl text-5xl font-extrabold leading-[1.02] tracking-[-.06em] text-[#123a40] sm:text-6xl">Kampus nyaman,<br>mulai dari kita.</h1>
                <p class="max-w-2xl text-lg leading-8 text-slate-600">Laporkan masalah fasilitas di {{ config('campus.short_name') }}, pantau proses penanganannya, dan bantu menciptakan lingkungan belajar yang nyaman.</p>
                <p class="text-sm font-semibold text-teal-800">{{ config('campus.motto') }}</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('guest.issues.create') }}" class="cf-button cf-button--primary">Buat laporan</a>
                    <a href="{{ route('register') }}" class="cf-button cf-button--secondary">Buat akun</a>
                </div>
            </div>

            <div class="cf-campus-art min-h-[22rem] rounded-[2rem] shadow-sm" role="img" aria-label="Gedung Perpustakaan Digital Universitas Negeri Medan"></div>
        </section>

        <section class="grid gap-4 border-t border-slate-200 py-8 sm:grid-cols-3" aria-label="Cara kerja CampusFix">
            @foreach ([['01', 'Laporkan', 'Ceritakan kendala dan lokasi fasilitas.'], ['02', 'Pantau', 'Koordinator memeriksa lalu menugaskan tim.'], ['03', 'Konfirmasi', 'Tinjau hasil perbaikan dan beri umpan balik.']] as [$number, $title, $description])
                <article class="cf-stat">
                    <span class="cf-eyebrow">{{ $number }}</span>
                    <h2 class="mt-2 font-bold text-slate-800">{{ $title }}</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $description }}</p>
                </article>
            @endforeach
        </section>

        <footer class="grid gap-3 border-t border-slate-200 py-7 text-sm text-slate-600 sm:grid-cols-2">
            <p><strong class="text-slate-800">{{ config('campus.name') }}</strong><br>{{ config('campus.address') }}</p>
            <p class="sm:text-right">Bantuan: <a class="font-semibold text-teal-800 hover:underline" href="mailto:{{ config('campus.contact_email') }}">{{ config('campus.contact_email') }}</a><br>{{ config('campus.contact_phone') }}</p>
        </footer>
    </main>
</body>
</html>
