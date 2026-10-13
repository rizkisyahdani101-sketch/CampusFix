<x-portal-layout :title="$title">
    @if ($user->role === \App\Models\User::ROLE_MEMBER)
        <div class="cf-member-home">
            <header class="cf-member-welcome">
                <div>
                    @php
                        $hour = now('Asia/Jakarta')->hour;
                        $greeting = match (true) {
                            $hour < 11 => 'Selamat pagi',
                            $hour < 15 => 'Selamat siang',
                            $hour < 18 => 'Selamat sore',
                            default => 'Selamat malam',
                        };
                    @endphp
                    <h1><span data-campus-greeting data-timezone="Asia/Jakarta">{{ $greeting }}</span>, {{ $user->name }} <span aria-hidden="true">👋</span></h1>
                    <p>Ada yang perlu diperbaiki? Yuk, bantu kampus jadi lebih nyaman.</p>
                </div>
                <a href="{{ route('issues.create') }}" class="cf-button cf-button--primary">＋ Laporkan masalah</a>
            </header>

            <section class="cf-home-hero" aria-label="Ajakan melaporkan fasilitas kampus">
                <div class="cf-home-hero-copy">
                    <span class="cf-home-desktop-only">Suaramu, perubahan kita</span>
                    <span class="cf-home-mobile-only">Pesan kecil, perubahan besar</span>
                    <h2 class="cf-home-desktop-only">Fasilitas lebih baik.<br>Belajar lebih nyaman.</h2>
                    <h2 class="cf-home-mobile-only">Kampus nyaman,<br>mulai dari kita.</h2>
                    <p class="cf-home-desktop-only">Laporkan kendala, pantau prosesnya, rasakan perubahannya.</p>
                    <p class="cf-home-mobile-only">Temukan kendala fasilitas? Laporkan dan pantau perbaikannya di sini.</p>
                    <svg class="cf-home-hero-icon" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M5 27V12l11-6 11 6v15M11 27v-8h10v8M2.5 27h27M16 12v5m-2.5-2.5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <a href="{{ route('issues.create') }}" class="cf-button cf-home-hero-cta mt-4 border border-white/20 bg-white/10 text-white hover:bg-white/20">＋ Laporkan masalah</a>
                </div>
                <div class="cf-campus-photo" role="img" aria-label="Gedung Fakultas Kedokteran Universitas Negeri Medan"></div>
            </section>

            <section class="cf-home-stats" aria-label="Ringkasan laporan saya">
                @foreach ([
                    ['Total laporan', $totalIssues, '▤', 'Kontribusimu untuk kampus'],
                    ['Dalam proses', $activeIssues, '◷', 'Sedang ditindaklanjuti'],
                    ['Selesai', $counts['resolved'] + $counts['closed'], '✓', 'Fasilitas kembali nyaman'],
                ] as [$label, $value, $icon, $note])
                    <article class="cf-home-stat">
                        <span class="cf-home-stat-icon" aria-hidden="true">{{ $icon }}</span>
                        <div><p>{{ $label }}</p><strong>{{ $value }}</strong><small class="cf-home-stat-note">{{ $note }}</small></div>
                    </article>
                @endforeach
            </section>

            <section id="categories" aria-labelledby="categories-title">
                <div class="cf-home-section-title">
                    <h2 id="categories-title">Apa yang bermasalah?</h2>
                    <span class="text-xs text-slate-500">Pilih kategori untuk mulai melapor</span>
                </div>
                <div class="cf-category-grid">
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
                        <a class="cf-category-card" href="{{ route('issues.create') }}"><span aria-hidden="true">{{ $categoryIcon }}</span>{{ $category->name }}</a>
                    @endforeach
                </div>
            </section>

            <div class="cf-home-lower">
                <section id="my-issues" class="cf-home-panel" aria-labelledby="my-issues-title">
                    <div class="cf-home-section-title">
                        <h2 id="my-issues-title">Laporan saya</h2>
                        <a href="{{ route('reporter.issues.confirmation') }}">Lihat semua →</a>
                    </div>
                    @if ($recentIssues->isEmpty())
                        <div class="cf-empty-state">Belum ada laporan. Pilih kategori masalah untuk mengirim laporan pertama.</div>
                    @else
                        <ul class="cf-home-list">
                            @foreach ($recentIssues as $issue)
                                <li class="cf-home-list-item">
                                    <div class="min-w-0">
                                        <a class="block truncate" href="{{ route('issues.show', $issue) }}">{{ $issue->title }}</a>
                                        <p>{{ $issue->category?->name ?? 'Fasilitas' }} · {{ $issue->location?->name ?? 'Lokasi kampus' }}@if ($issue->location_detail) · {{ $issue->location_detail }}@endif · {{ $issue->created_at?->diffForHumans() }}</p>
                                    </div>
                                    <span class="cf-badge cf-badge--teal">{{ str($issue->status)->replace('_', ' ')->title() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section id="notifications" class="cf-home-panel" aria-labelledby="news-title">
                    <div class="cf-home-section-title">
                        <h2 id="news-title">Kabar terbaru</h2>
                        @if ($unreadNotificationCount > 0)<span class="cf-badge cf-badge--teal">{{ $unreadNotificationCount }} baru</span>@endif
                    </div>
                    @if ($notifications->isEmpty())
                        <p class="text-sm text-slate-500">Pembaruan laporanmu akan muncul di sini.</p>
                    @else
                        <ul class="space-y-2.5">
                            @foreach ($notifications as $notification)
                                <li class="border-b border-slate-100 pb-2.5 last:border-0 last:pb-0">
                                    <p class="text-xs font-semibold text-slate-700">{{ $notification->data['issue_title'] ?? 'Laporan CampusFix' }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $notification->data['message'] ?? 'Status '.str($notification->data['to_status'] ?? 'diperbarui')->replace('_', ' ')->title() }}</p>
                                    <time class="mt-1 block text-[.65rem] text-slate-400">{{ $notification->created_at->diffForHumans() }}</time>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        </div>
    @else
    <div class="space-y-7">
        <header class="cf-page-header">
            <div>
                <p class="cf-eyebrow">Ruang kerja CampusFix</p>
                <h1 class="cf-page-title">{{ $title }}</h1>
                <p class="mt-2 text-sm text-slate-600">Halo, {{ $user->name }}. Berikut perkembangan laporan fasilitas kampus.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" class="cf-button cf-button--primary">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan laporan">
            @foreach ([
                'Total laporan' => $totalIssues,
                'Laporan aktif' => $activeIssues,
                'Menunggu verifikasi' => $counts['reported'],
                'Selesai' => $counts['resolved'] + $counts['closed'],
            ] as $label => $value)
                <article class="cf-stat">
                    <p class="cf-stat-label">{{ $label }}</p>
                    <p class="cf-stat-value">{{ $value }}</p>
                </article>
            @endforeach
            @if ($userCount !== null)
                <article class="cf-stat">
                    <p class="cf-stat-label">Pengguna terdaftar</p>
                    <p class="cf-stat-value">{{ $userCount }}</p>
                </article>
            @endif
            @if ($averageFeedback !== null)
                <article class="cf-stat">
                    <p class="cf-stat-label">Rata-rata feedback</p>
                    <p class="cf-stat-value">{{ number_format((float) $averageFeedback, 1) }} <span class="text-base font-semibold text-slate-500">/ 5</span></p>
                </article>
            @endif
        </section>

        <section class="cf-panel">
            <h2 class="cf-panel-heading">Laporan terbaru</h2>
            @if ($recentIssues->isEmpty())
                <p class="px-5 py-9 text-sm text-slate-600">Belum ada laporan pada ruang kerja ini.</p>
            @else
                <div class="cf-table-wrap">
                    <table class="cf-table">
                        <thead>
                            <tr>
                                <th>Laporan</th>
                                <th>Kategori</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentIssues as $issue)
                                <tr>
                                    <td><a href="{{ route('issues.show', $issue) }}">#{{ $issue->id }} · {{ $issue->title }}</a></td>
                                    <td>{{ $issue->category?->name ?? '—' }}</td>
                                    <td>{{ $issue->location?->full_path ?? '—' }}@if ($issue->location_detail)<span class="block text-xs text-slate-500">{{ $issue->location_detail }}</span>@endif</td>
                                    <td><span class="cf-badge cf-badge--teal">{{ str($issue->status)->replace('_', ' ')->title() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="cf-panel">
            <div class="flex flex-wrap items-center justify-between gap-2 cf-panel-heading">
                <h2>Notifikasi</h2>
                <span class="text-sm font-medium text-slate-500">{{ $unreadNotificationCount }} belum dibaca</span>
            </div>
            @if ($notifications->isEmpty())
                <p class="px-5 py-7 text-sm text-slate-600">Belum ada notifikasi.</p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($notifications as $notification)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 {{ $notification->read_at ? 'bg-white' : 'bg-teal-50/50' }}">
                            <div>
                                <p class="font-semibold">{{ $notification->data['issue_title'] ?? 'Laporan CampusFix' }}</p>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $notification->data['message'] ?? 'Status '.str($notification->data['to_status'] ?? 'diperbarui')->replace('_', ' ')->title() }}
                                    · {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @if (! $notification->read_at && ! empty($notification->data['issue_id']))
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button class="cf-button cf-button--secondary">Buka laporan</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
    @endif
</x-portal-layout>
