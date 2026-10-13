<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Lapor tanpa akun · {{ config('campus.brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="cf-app antialiased">
    <header class="cf-topbar">
        <div class="cf-topbar-inner">
            <a href="{{ route('guest.issues.create') }}" class="cf-brand"><x-campusfix-mark />CampusFix</a>
            <a href="{{ route('login') }}" class="cf-button cf-button--secondary">Masuk dengan akun</a>
        </div>
    </header>
    <x-campus-announcement-ticker />
    <main class="cf-page cf-page--narrow space-y-6">
        <header class="space-y-4">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-teal-800"><span aria-hidden="true" class="text-lg">←</span> Kembali ke masuk</a>
            <div>
                <span class="mb-3 inline-flex rounded-full bg-teal-50 px-3 py-1.5 text-xs font-semibold tracking-wide text-teal-800">LAPORAN PENGUNJUNG · {{ config('campus.short_name') }}</span>
                <h1 class="text-3xl font-bold tracking-tight text-[#17343a]">Laporkan masalah</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600 sm:text-base">Laporkan masalah fasilitas {{ config('campus.name') }}. Akun tidak diperlukan; email aktif akan dikonfirmasi sebelum laporan dikirim.</p>
            </div>
        </header>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                {{ session('status') }}
                @if (config('mail.default') === 'log')
                    <span class="mt-2 block text-sm">Mode lokal: tautan verifikasi dicatat di <code>storage/logs/laravel.log</code>.</span>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('guest.issues.store') }}" enctype="multipart/form-data" class="cf-form cf-panel cf-panel-body flex flex-col gap-6">
            @csrf

            <div class="order-6 grid gap-5 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2 sm:p-5">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium">Nama</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10" />
                    @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email untuk verifikasi</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="anda@contoh.com" class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10" />
                    <p class="mt-1 text-xs text-slate-500">Email aktif apa pun boleh digunakan, tidak harus email UNIMED.</p>
                    @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="order-2">
                <label for="title" class="mb-1 block text-sm font-medium">Judul masalah</label>
                <input id="title" name="title" value="{{ old('title') }}" required maxlength="150" placeholder="Contoh: Wi-Fi lantai 3 tidak berfungsi" class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10" />
                @error('title') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="order-3">
                <label for="description" class="mb-1 block text-sm font-medium">Deskripsi</label>
                <textarea id="description" name="description" rows="4" required minlength="10" maxlength="10000" class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10" placeholder="Ceritakan apa yang terjadi dan sejak kapan.">{{ old('description') }}</textarea>
                @error('description') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div class="order-1 space-y-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800">Kategori masalah <span class="text-rose-600">*</span></label>
                    @if ($categories->isEmpty())
                        <p class="cf-alert cf-alert--warning text-sm">Kategori laporan belum tersedia. Admin perlu menjalankan seeder kategori sebelum laporan dapat dibuat.</p>
                    @else
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        @foreach ($categories as $category)
                            @php
                                $categoryName = str($category->name)->lower()->toString();
                                $categoryIcon = match (true) {
                                    str_contains($categoryName, 'wi-fi'), str_contains($categoryName, 'wifi'), str_contains($categoryName, 'jaringan') => '⌁',
                                    str_contains($categoryName, 'lampu'), str_contains($categoryName, 'listrik') => '☼',
                                    str_contains($categoryName, 'ac'), str_contains($categoryName, 'udara') => '❄',
                                    str_contains($categoryName, 'proyektor') => '▣',
                                    str_contains($categoryName, 'kursi'), str_contains($categoryName, 'mebel') => '▤',
                                    str_contains($categoryName, 'toilet'), str_contains($categoryName, 'air') => '♧',
                                    default => '＋',
                                };
                            @endphp
                            <label class="cursor-pointer">
                                <input type="radio" name="category_id" value="{{ $category->getKey() }}" required @checked(old('category_id') == $category->getKey()) class="peer sr-only">
                                <span class="flex min-h-12 items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 transition hover:border-teal-300 hover:bg-teal-50/50 peer-checked:border-teal-700 peer-checked:bg-teal-50 peer-checked:text-teal-900 peer-focus-visible:ring-4 peer-focus-visible:ring-teal-600/15">
                                    <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-lg text-teal-800">{{ $categoryIcon }}</span>{{ $category->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @endif
                    @error('category_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="location_id" class="mb-1 block text-sm font-medium">Fakultas, gedung, atau fasilitas</label>
                    <select id="location_id" name="location_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
                        <option value="">Pilih lokasi di kampus UNIMED</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->getKey() }}" @selected(old('location_id') == $location->getKey())>{{ $location->full_path }}</option>
                        @endforeach
                    </select>
                    @error('location_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    <div class="mt-3">
                        <label for="location_detail" class="mb-1 block text-sm font-medium">Detail lokasi <span class="font-normal text-slate-500">(opsional)</span></label>
                        <input id="location_detail" name="location_detail" value="{{ old('location_detail') }}" maxlength="255" placeholder="Contoh: lantai 2, ruang 204, dekat tangga" class="w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10" />
                        @error('location_detail') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Direktori ruang belum lengkap. Pilih fakultas/gedung yang paling sesuai lalu isi detail lantai atau ruang jika diketahui.</p>
                </div>
            </div>

            <div class="order-5 space-y-2 rounded-2xl border border-slate-200 p-4">
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="safety_flag" value="1" @checked(old('safety_flag')) class="rounded border-slate-300 text-teal-700 focus:ring-teal-600"> Ada risiko keselamatan</label>
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="class_blocked" value="1" @checked(old('class_blocked')) class="rounded border-slate-300 text-teal-700 focus:ring-teal-600"> Kegiatan kelas terganggu</label>
            </div>

            <div class="order-4">
                <label for="photo" class="mb-1 block text-sm font-medium">Foto (opsional, maksimal 2 MB)</label>
                <input id="photo" type="file" name="photo" accept="image/*" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:font-semibold file:text-teal-800" />
                @error('photo') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            @if ($categories->isEmpty() || $locations->isEmpty())
                <p class="order-7 rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Kategori atau data lokasi belum tersedia. Laporan belum dapat dikirim.</p>
            @endif

            <div class="order-8 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                <button type="submit" @disabled($categories->isEmpty() || $locations->isEmpty()) class="cf-button cf-button--primary">Kirim tautan verifikasi</button>
                <a href="{{ route('login') }}" class="cf-button cf-button--secondary">Masuk dengan akun</a>
            </div>
        </form>

        <aside class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
            <h2 class="font-semibold text-slate-800">Kontak resmi UNIMED</h2>
            <p class="mt-2">{{ config('campus.address') }}</p>
            <p class="mt-2"><a class="font-semibold text-teal-800 hover:underline" href="mailto:{{ config('campus.contact_email') }}">{{ config('campus.contact_email') }}</a> · {{ config('campus.contact_phone') }}</p>
        </aside>
    </main>
</body>
</html>
