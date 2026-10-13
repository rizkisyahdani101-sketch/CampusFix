<x-auth-layout title="Buat kata sandi baru" description="Pilih kata sandi baru untuk akun CampusFix UNIMED-mu.">
    @if ($errors->any())
        <div role="alert" class="cf-alert cf-alert--error mb-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="cf-auth-form cf-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="cf-label">Email kampus / akun</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="cf-input">
        </div>
        <div>
            <label for="password" class="cf-label">Kata sandi baru</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="cf-input">
        </div>
        <div>
            <label for="password_confirmation" class="cf-label">Konfirmasi kata sandi</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="cf-input">
        </div>
        <button type="submit" class="cf-button cf-button--primary w-full">Simpan kata sandi baru</button>
    </form>
</x-auth-layout>
