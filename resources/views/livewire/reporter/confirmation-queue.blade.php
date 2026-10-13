<section class="cf-page space-y-6">
    <header>
        <p class="cf-eyebrow">Pelapor · Tinjau hasil perbaikan</p>
        <h1 class="cf-page-title">Konfirmasi laporan</h1>
        <p class="mt-2 text-sm text-slate-600">Pastikan masalah sudah ditangani. Jika belum, buka kembali laporan dan jelaskan alasannya.</p>
    </header>

    @if (session()->has('workflow-status'))
        <div role="status" class="cf-alert cf-alert--success">{{ session('workflow-status') }}</div>
    @endif

    @forelse ($issues as $issue)
        @php
            $status = $issue->status instanceof \App\Enums\IssueStatus
                ? $issue->status->value
                : (string) $issue->status;
        @endphp

        <article wire:key="reporter-confirmation-{{ $issue->getKey() }}" class="cf-panel cf-panel-body space-y-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Laporan #{{ $issue->getKey() }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $issue->title ?: 'Laporan #'.$issue->getKey() }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $issue->description }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <span class="cf-badge cf-badge--teal">{{ str($status)->replace('_', ' ')->title() }}</span>
                    @if ($issue->location)<span>{{ $issue->location->full_path }}</span>@endif
                    @if ($issue->location_detail)<span class="text-slate-500">{{ $issue->location_detail }}</span>@endif
                </div>
            </div>

            <div class="grid gap-5 border-t border-slate-100 pt-5 lg:grid-cols-2">
                @if ($status === 'resolved')
                    <form wire:submit.prevent="closeIssue({{ $issue->getKey() }})" class="cf-form space-y-3">
                        <div>
                            <label for="feedback-rating-{{ $issue->getKey() }}" class="cf-label">Bagaimana hasil perbaikannya?</label>
                            <select id="feedback-rating-{{ $issue->getKey() }}" wire:model="feedbackForms.{{ $issue->getKey() }}.rating" required class="cf-input">
                                <option value="">Pilih penilaian</option>
                                <option value="5">5 · Sangat puas</option>
                                <option value="4">4 · Puas</option>
                                <option value="3">3 · Cukup</option>
                                <option value="2">2 · Kurang puas</option>
                                <option value="1">1 · Tidak puas</option>
                            </select>
                            @error('feedbackForms.'.$issue->getKey().'.rating')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="feedback-comment-{{ $issue->getKey() }}" class="cf-label">Catatan (opsional)</label>
                            <textarea id="feedback-comment-{{ $issue->getKey() }}" wire:model="feedbackForms.{{ $issue->getKey() }}.comment" rows="2" maxlength="2000" class="cf-input" placeholder="Bagikan masukan tentang perbaikan."></textarea>
                            @error('feedbackForms.'.$issue->getKey().'.comment')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="cf-button cf-button--primary">Konfirmasi dan tutup</button>
                    </form>
                @endif

                <form wire:submit.prevent="reopenIssue({{ $issue->getKey() }})" class="cf-form space-y-3">
                    <div>
                        <label for="reopen-reason-{{ $issue->getKey() }}" class="cf-label">{{ $status === 'closed' ? 'Buka kembali laporan' : 'Masalah masih ada?' }}</label>
                        <textarea id="reopen-reason-{{ $issue->getKey() }}" wire:model.defer="reopenReasons.{{ $issue->getKey() }}" rows="3" maxlength="2000" required class="cf-input" placeholder="Jelaskan apa yang masih perlu diperbaiki."></textarea>
                        @error('reopenReasons.'.$issue->getKey())<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="cf-button cf-button--warning">Buka kembali laporan</button>
                </form>
            </div>
        </article>
    @empty
        <div class="cf-empty-state">
            <p class="font-semibold text-slate-700">Tidak ada laporan untuk dikonfirmasi</p>
            <p class="mt-1 text-sm">Laporan yang sudah diperbaiki akan muncul di sini.</p>
        </div>
    @endforelse

    {{ $issues->links() }}
</section>
