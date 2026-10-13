<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $this->currentUser($request);

        return view('auth.profile', [
            'user' => $user,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'min:8', 'different:current_password'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        if (! Hash::check($validated['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'Kata sandi saat ini salah.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        // Confirm the current session so it survives password_hash invalidation.
        \Auth::logoutOtherDevices($validated['password']);
        $request->session()->regenerate();

        \Log::info('User changed password', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('account.profile')
            ->with('success', 'Kata sandi berhasil diubah. Perangkat lain yang masuk dengan akun ini telah otomatis keluar.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.required' => 'Pilih berkas foto terlebih dahulu.',
            'avatar.image' => 'Berkas harus berupa gambar (JPG, PNG, atau WebP).',
            'avatar.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'avatar.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $previousAvatar = $user->avatar;
        $newAvatar = $validated['avatar']->store('avatars', 'public');

        $user->forceFill(['avatar' => $newAvatar])->save();

        $this->deleteAvatarFile($previousAvatar, $newAvatar);

        \Log::info('User updated avatar', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('account.profile')
            ->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);
        $previousAvatar = $user->avatar;

        $user->forceFill(['avatar' => null])->save();

        $this->deleteAvatarFile($previousAvatar);

        \Log::info('User removed avatar', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('account.profile')
            ->with('success', 'Foto profil dihapus. Ikon inisial nama dipakai kembali.');
    }

    private function deleteAvatarFile(?string $path, ?string $keep = null): void
    {
        if (! is_string($path) || trim($path) === '' || $path === $keep) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function currentUser(Request $request): User
    {
        $user = $request->user();

        abort_if($user === null, 403);

        return $user;
    }
}
