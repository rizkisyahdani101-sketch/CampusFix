<?php

use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component {
    public Issue $issue;

    public function mount(Issue $issue): void
    {
        Gate::authorize('view', $issue);

        $this->issue = $issue->load([
            'category',
            'location.parent',
            'mergedInto',
            'reporters',
            'attachments',
            'statusHistories.actor',
            'comments.user',
            'assignments.team',
            'assignments.technician',
            'assignments.resolution',
            'resolutions.resolvedBy',
        ]);
    }

    public function render()
    {
        return $this->view();
    }
};
?>

@php
    $status = $issue->status instanceof IssueStatus ? $issue->status->value : (string) $issue->status;
    $progressStep = match ($status) {
        'reported' => 1,
        'verified', 'assigned' => 2,
        'in_progress', 'on_hold', 'reopened' => 3,
        'resolved', 'closed' => 4,
        default => 0,
    };
    $statusTone = match ($status) {
        'rejected', 'merged' => 'cf-badge--danger',
        'on_hold' => 'cf-badge--warning',
        'resolved', 'closed' => 'cf-badge--success',
        default => 'cf-badge--teal',
    };
@endphp

<div class="cf-page cf-page--narrow cf-issue-detail space-y-5">
    <header class="space-y-3">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-teal-800 hover:underline">
            <span aria-hidden="true">←</span> Kembali ke laporan saya
        </a>
    </header>

    <article class="cf-panel cf-panel-body space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="cf-eyebrow">Laporan CF-{{ str_pad((string) $issue->getKey(), 4, '0', STR_PAD_LEFT) }} · {{ $issue->category?->name ?? 'Fasilitas kampus' }}</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $issue->title }}</h1>
                <p class="mt-2 text-sm text-slate-500">{{ $issue->location?->full_path ?? 'Lokasi tidak tersedia' }}@if ($issue->location_detail) · {{ $issue->location_detail }}@endif · Dilaporkan {{ $issue->created_at?->format('d M Y, H:i') }}</p>
            </div>
            <span class="cf-badge {{ $statusTone }}">{{ str($status)->replace('_', ' ')->title() }}</span>
        </div>

        @if ($status === 'in_progress' || $status === 'assigned')
            <div class="cf-alert cf-alert--info flex flex-wrap items-center justify-between gap-3">
                <span class="font-semibold">Petugas sedang menangani laporanmu</span>
                <span class="text-xs">Diperbarui {{ $issue->updated_at?->format('d.m H:i') }}</span>
            </div>
        @endif

        <div class="cf-issue-columns">
            <div class="cf-issue-primary space-y-5">
                <section>
                    <h2 class="font-semibold text-slate-800">Detail masalah</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $issue->description }}</p>
                </section>

                @if (auth()->user()?->role === \App\Models\User::ROLE_MEMBER && $status !== IssueStatus::Merged->value)
                    <livewire:issues.me-too :issue="$issue" />
                @endif

                <section class="border-t border-slate-100 pt-4">
                    <h2 class="font-semibold text-slate-800">Informasi tambahan</h2>
                    <div class="mt-3 flex flex-wrap gap-2 text-sm">
                        <span class="cf-badge {{ $issue->safety_flag ? 'cf-badge--warning' : 'cf-badge--teal' }}">{{ $issue->safety_flag ? 'Perlu perhatian keselamatan' : 'Tidak ada risiko keselamatan' }}</span>
                        <span class="cf-badge {{ $issue->class_blocked ? 'cf-badge--warning' : 'cf-badge--teal' }}">{{ $issue->class_blocked ? 'Kegiatan kelas terganggu' : 'Kegiatan kelas berjalan' }}</span>
                    </div>
                </section>

                @if ($issue->attachments->isNotEmpty())
                    <section class="border-t border-slate-100 pt-4">
                        <h2 class="font-semibold text-slate-800">Foto laporan</h2>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($issue->attachments as $attachment)
                                <a href="{{ asset('storage/'.$attachment->file_path) }}" target="_blank" rel="noopener noreferrer" class="group overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                    <img src="{{ asset('storage/'.$attachment->file_path) }}" alt="{{ $attachment->original_name }}" class="max-h-80 w-full object-cover transition group-hover:scale-[1.02]">
                                    <span class="block px-3 py-2 text-xs text-slate-500">{{ $attachment->original_name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($issue->resolutions->isNotEmpty())
                    <section class="space-y-3 border-t border-slate-100 pt-4">
                        <h2 class="font-semibold text-slate-800">Catatan penyelesaian</h2>
                        @foreach ($issue->resolutions as $resolution)
                            <div class="space-y-2 rounded-xl bg-teal-50/70 p-4 text-sm text-teal-950">
                                <p><span class="font-semibold">Penyebab:</span> {{ $resolution->root_cause }}</p>
                                <p><span class="font-semibold">Tindakan:</span> {{ $resolution->action_taken }}</p>
                                <p><span class="font-semibold">Suku cadang:</span> {{ $resolution->parts_used ?: '—' }}</p>
                                <p><span class="font-semibold">Waktu pengerjaan:</span> {{ $resolution->minutes_spent }} menit</p>
                            </div>
                        @endforeach
                    </section>
                @endif

                <section class="border-t border-slate-100 pt-4">
                    <h2 class="font-semibold text-slate-800">Pembaruan pekerjaan</h2>
                    <ul class="mt-3 space-y-2">
                        @forelse ($issue->comments as $comment)
                            <li class="rounded-xl bg-slate-50 p-3 text-sm">
                                <p class="whitespace-pre-line">{{ $comment->body }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ $comment->user?->name ?? 'Tim teknisi' }} · {{ $comment->created_at?->format('d M Y, H:i') }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-slate-500">Belum ada pembaruan pekerjaan.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            <aside class="cf-issue-side space-y-4">
        @if ($progressStep > 0)
            <section class="cf-panel-body space-y-4 rounded-2xl bg-[#edf5f7] p-4 sm:p-5" aria-label="Progress perbaikan">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-slate-800">Progress penyelesaian</h3>
                    <span class="text-sm font-bold text-teal-800">{{ $progressStep }} / 4 <span class="ml-1 text-xs text-slate-500">{{ $progressStep * 25 }}%</span></span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Tahap penyelesaian" aria-valuemin="0" aria-valuemax="4" aria-valuenow="{{ $progressStep }}">
                    <div class="h-full rounded-full bg-teal-700 transition-all" style="width: {{ $progressStep * 25 }}%"></div>
                </div>
                <ol class="space-y-3 text-xs sm:text-sm">
                    @foreach (['Laporan diterima', 'Diverifikasi', 'Sedang ditangani', 'Selesai'] as $index => $stepLabel)
                        @php($stepNumber = $index + 1)
                        <li class="flex items-start gap-3 {{ $stepNumber <= $progressStep ? 'font-semibold text-teal-900' : 'text-slate-500' }}">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full {{ $stepNumber < $progressStep ? 'bg-teal-700 text-white' : ($stepNumber === $progressStep ? 'border-2 border-teal-700 bg-white text-teal-800' : 'bg-slate-200 text-slate-500') }}">{{ $stepNumber < $progressStep ? '✓' : $stepNumber }}</span>
                            <span>{{ $stepLabel }}<small class="mt-1 block font-normal text-slate-500">{{ $stepNumber < $progressStep ? 'Selesai' : ($stepNumber === $progressStep ? 'Sedang diproses' : 'Menunggu pembaruan') }}</small></span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @else
            <div class="cf-alert {{ $status === 'rejected' ? 'cf-alert--error' : 'cf-alert--warning' }}">
                @if ($issue->mergedInto)
                    Laporan ini digabung sebagai duplikat dari
                    <a class="font-semibold underline" href="{{ route('issues.show', $issue->mergedInto) }}">laporan #{{ $issue->mergedInto->getKey() }}</a>.
                @elseif ($status === 'rejected')
                    Laporan ini tidak dilanjutkan. Lihat riwayat untuk keterangan dari koordinator.
                @else
                    Laporan ini tidak lagi berada dalam antrean aktif.
                @endif
            </div>
        @endif

            <dl class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm">
                <div><dt class="text-xs text-slate-500">Kategori</dt><dd class="mt-1 font-semibold">{{ $issue->category?->name ?? 'Tidak tersedia' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Prioritas</dt><dd class="mt-1 font-semibold">{{ ucfirst($issue->priority ?? 'medium') }}</dd></div>
                <div><dt class="text-xs text-slate-500">Pelapor</dt><dd class="mt-1 font-semibold">{{ $issue->reporters->pluck('name')->join(', ') ?: 'Tidak tersedia' }}</dd></div>
            </dl>

            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="font-semibold text-slate-800">Riwayat status</h2>
                <ol class="mt-3 space-y-4">
                @forelse ($issue->statusHistories as $history)
                    <li class="relative border-l-2 border-teal-200 pl-4 text-sm">
                        <span class="absolute -left-[5px] top-1 size-2 rounded-full bg-teal-600"></span>
                        <p class="font-semibold text-slate-800">
                            {{ $history->from_status ? str($history->from_status)->replace('_', ' ')->title().' → ' : '' }}{{ str($history->to_status)->replace('_', ' ')->title() }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ $history->actor?->name ?? 'Sistem' }} · {{ $history->created_at?->format('d M Y, H:i') }}</p>
                        @if ($history->reason)<p class="mt-1 text-slate-600">{{ $history->reason }}</p>@endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Belum ada riwayat status.</li>
                @endforelse
                </ol>
            </section>
            </aside>
        </div>
    </article>
</div>
