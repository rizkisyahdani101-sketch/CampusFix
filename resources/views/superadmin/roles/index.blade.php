<x-portal-layout title="Daftar role">
    <div class="mx-auto max-w-4xl space-y-6">
        <header>
            <p class="cf-eyebrow">Pengaturan sistem</p>
            <h1 class="cf-page-title">Daftar role</h1>
            <p class="mt-2 text-sm text-slate-600">Tanggung jawab setiap peran di dalam workflow CampusFix.</p>
        </header>

        <section class="cf-panel cf-panel-body space-y-3">
            @foreach ($roles as $name => $role)
                <article class="cf-role-item">
                    <div>
                        <h2 class="font-semibold text-slate-800">{{ $role['label'] }}</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $role['description'] }}</p>
                    </div>
                    <span class="cf-badge cf-badge--teal">{{ $name }}</span>
                </article>
            @endforeach
        </section>
    </div>
</x-portal-layout>
