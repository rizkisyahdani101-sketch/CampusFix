<section class="cf-page space-y-6">
    <header class="cf-page-header">
        <div>
            <p class="cf-eyebrow">Teknisi · Ruang kerja</p>
            <h1 class="cf-page-title">Tugas saya</h1>
            <p class="mt-2 text-sm text-slate-600">Terima penugasan, catat progres, dan dokumentasikan penyelesaian.</p>
        </div>
    </header>

    @if (session()->has('workflow-status'))
        <div role="status" class="cf-alert cf-alert--success">{{ session('workflow-status') }}</div>
    @endif

    @forelse ($assignments as $assignment)
        @php
            $issue = $assignment->issue;
            $status = $issue->status instanceof \App\Enums\IssueStatus
                ? $issue->status->value
                : (string) $issue->status;
        @endphp

        <article wire:key="assignment-{{ $assignment->getKey() }}" class="cf-panel cf-panel-body space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Laporan #{{ $issue->getKey() }}</p>
                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $issue->title ?: 'Laporan #'.$issue->getKey() }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $issue->description }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700">{{ $issue->location?->full_path ?? 'Lokasi UNIMED belum tersedia' }}@if ($issue->location_detail) · {{ $issue->location_detail }}@endif</p>
                    <p class="mt-2 text-xs text-slate-500">Tim {{ $assignment->team?->name ?? '—' }} · Ditugaskan {{ $assignment->assigned_at?->diffForHumans() }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="cf-badge cf-badge--teal">{{ str($status)->replace('_', ' ')->title() }}</span>
                    @if ($assignment->accepted_at === null)
                        <button type="button" wire:click="accept({{ $assignment->getKey() }})" class="cf-button cf-button--primary">Terima tugas</button>
                    @elseif ($status === 'assigned' || $status === 'on_hold')
                        <button type="button" wire:click="startWork({{ $issue->getKey() }})" class="cf-button cf-button--primary">{{ $status === 'on_hold' ? 'Lanjutkan pekerjaan' : 'Mulai pekerjaan' }}</button>
                    @endif
                </div>
            </div>

            @if (in_array($status, ['in_progress', 'on_hold'], true))
                <section class="space-y-4 border-t border-slate-100 pt-5">
                    <div>
                        <h3 class="font-semibold text-slate-800">Pembaruan progres</h3>
                        @foreach ($commentsByIssue->get($issue->getKey(), collect()) as $comment)
                            <p class="mt-2 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                                {{ $comment->body }}
                                <span class="ml-2 text-xs text-slate-500">{{ $comment->created_at?->diffForHumans() }}</span>
                            </p>
                        @endforeach
                    </div>

                    <form wire:submit.prevent="updateProgress({{ $issue->getKey() }})" class="cf-form space-y-3">
                        <label for="progress-{{ $issue->getKey() }}" class="cf-label">Catat perkembangan pekerjaan</label>
                        <textarea id="progress-{{ $issue->getKey() }}" wire:model.defer="progressUpdates.{{ $issue->getKey() }}" rows="2" maxlength="5000" class="cf-input" placeholder="Tuliskan pekerjaan yang selesai atau langkah berikutnya."></textarea>
                        @error('progressUpdates.'.$issue->getKey())<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                        <button type="submit" class="cf-button cf-button--secondary">Simpan progres</button>
                    </form>
                </section>
            @endif

            @if ($status === 'in_progress')
                <form wire:submit.prevent="hold({{ $issue->getKey() }})" class="cf-form space-y-3 border-t border-slate-100 pt-5">
                    <div>
                        <label for="hold-{{ $issue->getKey() }}" class="cf-label">Tunda pekerjaan</label>
                        <input id="hold-{{ $issue->getKey() }}" type="text" wire:model.defer="holdReasons.{{ $issue->getKey() }}" maxlength="2000" class="cf-input" placeholder="Jelaskan mengapa pekerjaan perlu ditunda.">
                        @error('holdReasons.'.$issue->getKey())<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="cf-button cf-button--warning">Tandai tertunda</button>
                </form>

                <form wire:submit.prevent="resolve({{ $issue->getKey() }})" class="cf-form space-y-4 border-t border-slate-100 pt-5">
                    <div>
                        <h3 class="font-semibold text-slate-800">Catatan penyelesaian</h3>
                        <p class="mt-1 text-sm text-slate-500">Catatan ini akan terlihat oleh pelapor dan koordinator.</p>
                    </div>
                    <div>
                        <label for="root-cause-{{ $issue->getKey() }}" class="cf-label">Penyebab utama</label>
                        <textarea id="root-cause-{{ $issue->getKey() }}" wire:model.defer="resolutionForms.{{ $issue->getKey() }}.root_cause" rows="2" maxlength="5000" class="cf-input"></textarea>
                        @error('resolutionForms.'.$issue->getKey().'.root_cause')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="action-taken-{{ $issue->getKey() }}" class="cf-label">Tindakan perbaikan</label>
                        <textarea id="action-taken-{{ $issue->getKey() }}" wire:model.defer="resolutionForms.{{ $issue->getKey() }}.action_taken" rows="2" maxlength="5000" class="cf-input"></textarea>
                        @error('resolutionForms.'.$issue->getKey().'.action_taken')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="parts-used-{{ $issue->getKey() }}" class="cf-label">Suku cadang yang digunakan</label>
                            <input id="parts-used-{{ $issue->getKey() }}" type="text" wire:model.defer="resolutionForms.{{ $issue->getKey() }}.parts_used" maxlength="5000" class="cf-input">
                            @error('resolutionForms.'.$issue->getKey().'.parts_used')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="minutes-spent-{{ $issue->getKey() }}" class="cf-label">Waktu pengerjaan (menit)</label>
                            <input id="minutes-spent-{{ $issue->getKey() }}" type="number" min="0" wire:model.defer="resolutionForms.{{ $issue->getKey() }}.minutes_spent" class="cf-input">
                            @error('resolutionForms.'.$issue->getKey().'.minutes_spent')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button type="submit" class="cf-button cf-button--primary">Kirim penyelesaian</button>
                </form>
            @endif
        </article>
    @empty
        <div class="cf-empty-state">
            <p class="font-semibold text-slate-700">Belum ada tugas aktif</p>
            <p class="mt-1 text-sm">Penugasan baru dari koordinator akan muncul di sini.</p>
        </div>
    @endforelse

    {{ $assignments->links() }}
</section>
