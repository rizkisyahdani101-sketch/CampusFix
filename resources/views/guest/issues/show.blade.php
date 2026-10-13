<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Laporan #{{ $guestIssueReporter->issue->getKey() }} · {{ config('campus.brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="cf-app antialiased">
    @php($issue = $guestIssueReporter->issue)
    @php($status = $issue->status instanceof \App\Enums\IssueStatus ? $issue->status->value : (string) $issue->status)
    @php($progressStep = match ($status) { 'reported' => 1, 'verified', 'assigned' => 2, 'in_progress', 'on_hold', 'reopened' => 3, 'resolved', 'closed' => 4, default => 0 })

    <header class="cf-topbar">
        <div class="cf-topbar-inner">
            <a href="{{ route('guest.issues.create') }}" class="cf-brand"><x-campusfix-mark />CampusFix</a>
            <a href="{{ route('guest.issues.create') }}" class="cf-button cf-button--secondary">Buat laporan lain</a>
        </div>
    </header>
    <x-campus-announcement-ticker />
    <main class="cf-page cf-page--narrow space-y-6">
        <p class="text-sm text-slate-600">Tautan privat untuk {{ $guestIssueReporter->email }}</p>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{{ session('status') }}</div>
        @endif

        <article class="cf-panel cf-panel-body space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Laporan #{{ $issue->getKey() }}</p>
                    <h1 class="mt-1 text-2xl font-semibold">{{ $issue->title }}</h1>
                </div>
                <span class="rounded-full bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-800">{{ str($status)->replace('_', ' ')->title() }}</span>
            </div>

            <p class="whitespace-pre-line text-slate-700">{{ $issue->description }}</p>

            @if ($progressStep > 0)
                <section class="space-y-4 rounded-2xl bg-[#f3f8f7] p-4 sm:p-5" aria-label="Progress perbaikan">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-semibold text-slate-800">Progress penyelesaian</h2>
                        <span class="text-sm font-bold text-teal-800">{{ $progressStep }} / 4</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Tahap penyelesaian" aria-valuemin="0" aria-valuemax="4" aria-valuenow="{{ $progressStep }}">
                        <div class="h-full rounded-full bg-teal-700 transition-all" style="width: {{ $progressStep * 25 }}%"></div>
                    </div>
                    <ol class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4 sm:text-sm">
                        @foreach (['Laporan diterima', 'Diverifikasi', 'Sedang ditangani', 'Selesai'] as $index => $stepLabel)
                            @php($stepNumber = $index + 1)
                            <li class="flex items-center gap-2 {{ $stepNumber <= $progressStep ? 'font-semibold text-teal-900' : 'text-slate-500' }}">
                                <span class="grid size-6 shrink-0 place-items-center rounded-full {{ $stepNumber < $progressStep ? 'bg-teal-700 text-white' : ($stepNumber === $progressStep ? 'border-2 border-teal-700 bg-white text-teal-800' : 'bg-slate-200 text-slate-500') }}">{{ $stepNumber < $progressStep ? '✓' : $stepNumber }}</span>
                                {{ $stepLabel }}
                            </li>
                        @endforeach
                    </ol>
                </section>
            @else
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    @if ($status === 'rejected')
                        Laporan ini tidak dilanjutkan. Lihat riwayat untuk catatan koordinator.
                    @else
                        Laporan ini telah digabung dengan laporan lain.
                    @endif
                </section>
            @endif

            <dl class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                <div><dt class="text-sm text-slate-500">Kategori</dt><dd class="font-medium">{{ $issue->category?->name ?? '—' }}</dd></div>
                <div><dt class="text-sm text-slate-500">Lokasi</dt><dd class="font-medium">{{ $issue->location?->full_path ?? '—' }}@if ($issue->location_detail)<span class="block text-sm font-normal text-slate-600">{{ $issue->location_detail }}</span>@endif</dd></div>
                <div><dt class="text-sm text-slate-500">Prioritas</dt><dd class="font-medium">{{ ucfirst($issue->priority ?? 'medium') }}</dd></div>
                <div><dt class="text-sm text-slate-500">Dikirim</dt><dd class="font-medium">{{ $issue->created_at?->format('d M Y, H:i') }}</dd></div>
            </dl>

            @if ($issue->attachments->isNotEmpty())
                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                    @foreach ($issue->attachments as $attachment)
                        <a href="{{ asset('storage/'.$attachment->file_path) }}" target="_blank" rel="noopener" class="text-sm font-semibold text-teal-800 underline">{{ $attachment->original_name }}</a>
                    @endforeach
                </div>
            @endif

            @if ($issue->resolutions->isNotEmpty())
                @php($resolution = $issue->resolutions->first())
                <section class="space-y-2 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-950">
                    <h2 class="font-semibold">Catatan penyelesaian</h2>
                    <p><strong>Penyebab:</strong> {{ $resolution->root_cause }}</p>
                    <p><strong>Tindakan:</strong> {{ $resolution->action_taken }}</p>
                    @if ($resolution->parts_used)<p><strong>Bagian yang digunakan:</strong> {{ $resolution->parts_used }}</p>@endif
                </section>
            @endif

            <section class="space-y-3 border-t border-slate-100 pt-4">
                <h2 class="font-semibold">Riwayat status</h2>
                <ol class="space-y-3">
                    @foreach ($issue->statusHistories as $history)
                        <li class="border-l-2 border-teal-200 pl-3 text-sm">
                            <p class="font-medium">{{ str($history->to_status)->replace('_', ' ')->title() }}</p>
                            <p class="text-slate-500">{{ $history->created_at?->format('d M Y, H:i') }} · {{ $history->actor?->name ?? 'Pelapor / sistem' }}</p>
                            @if ($history->reason)<p class="mt-1 text-slate-700">{{ $history->reason }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>

            @if ($status === 'resolved')
                <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
                    <form method="POST" action="{{ URL::signedRoute('guest.issues.close', ['guestIssueReporter' => $guestIssueReporter]) }}">
                        @csrf
                        <button type="submit" class="cf-button cf-button--primary">Masalah sudah selesai</button>
                    </form>
                    <form method="POST" action="{{ URL::signedRoute('guest.issues.reopen', ['guestIssueReporter' => $guestIssueReporter]) }}" class="cf-form w-full space-y-2">
                        @csrf
                        <label for="reason" class="block text-sm font-medium">Masalah masih ada? Jelaskan agar laporan dibuka kembali.</label>
                        <textarea id="reason" name="reason" required maxlength="2000" rows="3" class="w-full rounded-lg border-slate-300"></textarea>
                        @error('reason') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        <button type="submit" class="cf-button cf-button--warning">Buka kembali</button>
                    </form>
                </div>
            @elseif ($status === 'closed')
                <form method="POST" action="{{ URL::signedRoute('guest.issues.reopen', ['guestIssueReporter' => $guestIssueReporter]) }}" class="cf-form space-y-2 border-t border-slate-100 pt-4">
                    @csrf
                    <label for="reason" class="block text-sm font-medium">Perlu membuka kembali laporan ini?</label>
                    <textarea id="reason" name="reason" required maxlength="2000" rows="3" class="w-full rounded-lg border-slate-300"></textarea>
                    @error('reason') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    <button type="submit" class="cf-button cf-button--warning">Buka kembali</button>
                </form>
            @endif
        </article>

        <p class="text-center text-xs text-slate-500">Simpan tautan privat ini. Siapa pun yang memilikinya dapat melihat dan mengubah status laporan ini.</p>
    </main>
</body>
</html>
