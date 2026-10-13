<section class="cf-page space-y-6">
    <header class="cf-page-header">
        <div>
            <p class="cf-eyebrow">Koordinator · Tindak lanjut</p>
            <h1 class="cf-page-title">Penugasan laporan</h1>
            <p class="mt-2 text-sm text-slate-600">Pilih tim dan teknisi untuk setiap laporan yang sudah diverifikasi.</p>
        </div>
        <a href="{{ route('coordinator.issues.review') }}" class="cf-button cf-button--secondary">Antrean verifikasi</a>
    </header>

    @if (session()->has('workflow-status'))
        <div role="status" class="cf-alert cf-alert--success">{{ session('workflow-status') }}</div>
    @endif

    @forelse ($issues as $issue)
        <article wire:key="issue-assignment-{{ $issue->getKey() }}" class="cf-panel cf-panel--overflow-visible cf-panel-body space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Laporan #{{ $issue->getKey() }}</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $issue->title ?: 'Laporan #'.$issue->getKey() }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $issue->description }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700">{{ $issue->location?->full_path ?? 'Lokasi UNIMED belum tersedia' }}@if ($issue->location_detail) · {{ $issue->location_detail }}@endif</p>
                    <p class="mt-2 text-xs text-slate-500">Dibuat {{ $issue->created_at?->diffForHumans() }}</p>
                </div>
                <span class="cf-badge cf-badge--teal">{{ str($issue->status instanceof \App\Enums\IssueStatus ? $issue->status->value : $issue->status)->replace('_', ' ')->title() }}</span>
            </div>

            <form wire:submit.prevent="assign({{ $issue->getKey() }})" class="cf-form grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <div>
                    <label for="team-{{ $issue->getKey() }}" class="cf-label">Tim penanganan</label>
                    <select id="team-{{ $issue->getKey() }}" wire:model.live="teamIds.{{ $issue->getKey() }}" required class="cf-input">
                        <option value="">Pilih tim</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->getKey() }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @error('teamIds.'.$issue->getKey())<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    @php
                        $matchingTechnicians = $technicians->filter(
                            fn ($technician) => $technician->teams->contains('id', (int) ($teamIds[$issue->getKey()] ?? 0)),
                        );
                    @endphp
                    <label for="technician-{{ $issue->getKey() }}" class="cf-label">Teknisi</label>
                    <select id="technician-{{ $issue->getKey() }}" wire:model.defer="technicianIds.{{ $issue->getKey() }}" required class="cf-input">
                        <option value="">Pilih teknisi</option>
                        @forelse ($matchingTechnicians as $technician)
                            <option value="{{ $technician->getKey() }}">{{ $technician->name }}</option>
                        @empty
                            @if (! empty($teamIds[$issue->getKey()] ?? null))
                                <option value="" disabled>Belum ada teknisi di tim ini</option>
                            @endif
                        @endforelse
                    </select>
                    @if (empty($teamIds[$issue->getKey()] ?? null))
                        <p class="mt-1 text-xs text-slate-500">Pilih tim penanganan untuk melihat teknisi.</p>
                    @elseif ($matchingTechnicians->isEmpty())
                        <p role="status" class="mt-1 text-xs text-amber-700">Belum ada teknisi di tim ini. Minta admin menambahkan anggota ke tim tersebut.</p>
                    @endif
                    @error('technicianIds.'.$issue->getKey())<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="cf-button cf-button--primary">
                    {{ $issue->status === \App\Enums\IssueStatus::Assigned->value ? 'Ubah penugasan' : 'Tugaskan teknisi' }}
                </button>
            </form>
        </article>
    @empty
        <div class="cf-empty-state">
            <p class="font-semibold text-slate-700">Semua laporan sudah ditangani</p>
            <p class="mt-1 text-sm">Belum ada laporan terverifikasi yang menunggu penugasan.</p>
        </div>
    @endforelse

    {{ $issues->links() }}
</section>
