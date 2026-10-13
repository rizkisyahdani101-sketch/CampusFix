<x-auth-layout title="Daftar akun" description="Yuk, bersama merawat fasilitas kampus UNIMED.">
    @if ($errors->any())
        <div role="alert" class="cf-alert cf-alert--error mb-5">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="cf-auth-form cf-form">
        @csrf

        <div>
            <label for="name" class="cf-label">Nama lengkap</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Nama Anda" class="cf-input">
        </div>

        <div>
            <label for="student_id" class="cf-label">NIM <span class="font-normal text-slate-400">(opsional)</span></label>
            <input type="text" id="student_id" name="student_id" value="{{ old('student_id') }}" maxlength="50" autocomplete="off" placeholder="Nomor induk mahasiswa" class="cf-input">
        </div>

        <div>
            <label for="email" class="cf-label">Email kampus</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="nama&#64;{{ config('campus.student_email_domain') }}" class="cf-input">
            <p class="mt-1 text-[.68rem] text-slate-500">Untuk akun mahasiswa UNIMED, gunakan email &#64;{{ config('campus.student_email_domain') }} yang aktif.</p>
        </div>

        <div>
            <label for="password" class="cf-label">Kata sandi</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" class="cf-input">
        </div>

        <div>
            <label for="password_confirmation" class="cf-label">Konfirmasi kata sandi</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi" class="cf-input">
        </div>

        <button type="submit" class="cf-button cf-button--primary w-full">Daftar sekarang <span aria-hidden="true">→</span></button>
    </form>

    <div class="cf-auth-footer">
        <p>Sudah punya akun? <a href="{{ route('login') }}">Masuk sekarang</a></p>
    </div>
</x-auth-layout>
