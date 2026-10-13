@if ($tickerText !== '')
    <div class="cf-announcement-ticker" role="region" aria-label="Informasi kampus">
        <span class="cf-announcement-label"><span aria-hidden="true">✦</span> INFO KAMPUS</span>
        <div class="cf-announcement-window">
            <div class="cf-announcement-track">
                <p class="cf-announcement-copy">{{ $tickerText }}</p>
                <p class="cf-announcement-copy" aria-hidden="true">{{ $tickerText }}</p>
            </div>
        </div>
    </div>
@endif
