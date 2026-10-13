<section class="cf-page space-y-6">
    <header class="cf-page-header">
        <div>
            <p class="cf-eyebrow">Koordinator · Tinjau laporan</p>
            <h1 class="cf-page-title">Antrean verifikasi</h1>
            <p class="mt-2 text-sm text-slate-600">Periksa laporan baru, tetapkan prioritas, lalu verifikasi atau tolak.</p>
        </div>
        <a href="{{ route('coordinator.issues.assign') }}" class="cf-button cf-button--secondary">Lihat penugasan</a>
    </header>

    @if (session()->has('workflow-status'))
        <div role="status" class="cf-alert cf-alert--success">{{ session('workflow-status') }}</div>
    @endif

    @forelse ($issues as $issue)
        <article wire:key="issue-review-{{ $issue->getKey() }}" class="cf-panel cf-panel-body space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1 space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Laporan #{{ $issue->getKey() }}</p>
                    <h2 class="text-lg font-bold text-slate-900">{{ $issue->title ?: 'Laporan #'.$issue->getKey() }}</h2>
                    <p class="text-sm leading-6 text-slate-600">{{ $issue->description }}</p>
                    <p class="text-sm font-semibold text-slate-700">{{ $issue->location?->full_path ?? 'Lokasi UNIMED belum tersedia' }}@if ($issue->location_detail) · {{ $issue->location_detail }}@endif</p>
                    <p class="text-xs text-slate-500">Dikirim {{ $issue->created_at?->diffForHumans() }}</p>
                </div>

                <form wire:submit.prevent="verify({{ $issue->getKey() }})" class="cf-form flex flex-wrap items-end gap-3">
                    <div class="min-w-36">
                        <label for="priority-{{ $issue->getKey() }}" class="cf-label">Prioritas</label>
                        <select id="priority-{{ $issue->getKey() }}" wire:model="priorities.{{ $issue->getKey() }}" class="cf-input">
                            <option value="medium">Menengah</option>
                            <option value="low">Rendah</option>
                            <option value="high">Tinggi</option>
                            <option value="critical">Kritis</option>
                        </select>
                    </div>
                    <button type="submit" class="cf-button cf-button--primary">Verifikasi</button>
                </form>
            </div>

            <form wire:submit.prevent="reject({{ $issue->getKey() }})" class="cf-form space-y-3 border-t border-slate-100 pt-5">
                <div>
                    <label for="rejection-reason-{{ $issue->getKey() }}" class="cf-label">Alasan penolakan</label>
                    <textarea id="rejection-reason-{{ $issue->getKey() }}" wire:model.defer="rejectionReasons.{{ $issue->getKey() }}" rows="2" maxlength="2000" class="cf-input" placeholder="Jelaskan alasan laporan tidak dapat ditindaklanjuti."></textarea>
                    @error('rejectionReasons.'.$issue->getKey())<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="cf-button cf-button--danger">Tolak laporan</button>
            </form>

            @if ($mergeCandidates->contains(fn ($candidate) => $candidate->getKey() !== $issue->getKey()
                && (string) $candidate->category_id === (string) $issue->category_id
                && (string) $candidate->location_id === (string) $issue->location_id
                && \Illuminate\Support\Str::lower(trim((string) $candidate->location_detail)) === \Illuminate\Support\Str::lower(trim((string) $issue->location_detail))))
                <form wire:submit.prevent="mergeDuplicate({{ $issue->getKey() }})" class="cf-form flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5">
                    <div class="min-w-64 flex-1">
                        <label for="duplicate-target-{{ $issue->getKey() }}" class="cf-label">Gabungkan sebagai duplikat dari</label>
                        <select id="duplicate-target-{{ $issue->getKey() }}" wire:model="duplicateTargets.{{ $issue->getKey() }}" required class="cf-input">
                            <option value="">Pilih laporan utama</option>
                            @foreach ($mergeCandidates as $candidate)
                                @if ($candidate->getKey() !== $issue->getKey()
                                    && (string) $candidate->category_id === (string) $issue->category_id
                                    && (string) $candidate->location_id === (string) $issue->location_id
                                    && \Illuminate\Support\Str::lower(trim((string) $candidate->location_detail)) === \Illuminate\Support\Str::lower(trim((string) $issue->location_detail)))
                                    <option value="{{ $candidate->getKey() }}">#{{ $candidate->getKey() }} · {{ $candidate->title }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('duplicateTargets.'.$issue->getKey())<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="cf-button cf-button--warning">Gabungkan duplikat</button>
                </form>
            @endif
        </article>
    @empty
        <div class="cf-empty-state">
            <p class="font-semibold text-slate-700">Tidak ada laporan baru</p>
            <p class="mt-1 text-sm">Laporan yang menunggu verifikasi akan muncul di sini.</p>
        </div>
    @endforelse

    {{ $issues->links() }}
</section>
