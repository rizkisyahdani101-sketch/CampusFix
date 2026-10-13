<div class="rounded-2xl border border-teal-100 bg-teal-50/70 p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-semibold text-teal-950">{{ $affectedCount }} orang terdampak</p>
            <p class="text-sm text-teal-900">Konfirmasi jika kamu juga mengalami masalah yang sama.</p>
        </div>
        @if ($canConfirm && ! $hasConfirmed && ! $isMerged)
            <button wire:click="confirmImpact" class="cf-button cf-button--primary">
                Saya juga terdampak
            </button>
        @elseif ($hasConfirmed)
            <span class="text-sm font-semibold text-teal-900">Dampakmu sudah tercatat</span>
        @endif
    </div>
    @if (session()->has('me-too-status'))
        <p role="status" class="mt-2 text-sm text-emerald-800">{{ session('me-too-status') }}</p>
    @endif
</div>
