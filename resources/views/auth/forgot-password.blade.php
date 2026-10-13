<x-auth-layout title="Atur ulang kata sandi" description="Kami akan mengirim tautan untuk membuat kata sandi baru.">
    @if (session('status'))
        <div role="status" class="cf-alert cf-alert--success mb-4">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="cf-alert cf-alert--error mb-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="cf-auth-form cf-form">
        @csrf
        <div>
            <label for="email" class="cf-label">Email kampus</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama&#64;{{ config('campus.student_email_domain') }}" class="cf-input">
        </div>
        <button type="submit" class="cf-button cf-button--primary w-full">Kirim tautan reset</button>
    </form>

    <div class="cf-auth-footer"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></div>
</x-auth-layout>
