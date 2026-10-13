<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\GuestIssueController;
use App\Http\Controllers\Auth\PasswordResetController;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login'));

Route::view('/bantuan', 'help.index')->name('help');

Route::get('/lapor-tanpa-akun', [GuestIssueController::class, 'create'])
    ->name('guest.issues.create');
Route::post('/lapor-tanpa-akun', [GuestIssueController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('guest.issues.store');
Route::get('/laporan-pengunjung/verifikasi/{token}', [GuestIssueController::class, 'verify'])
    ->where('token', '[a-f0-9]{64}')
    ->middleware(['signed', 'throttle:10,1'])
    ->name('guest.issues.verify');
Route::get('/laporan-pengunjung/{guestIssueReporter}', [GuestIssueController::class, 'show'])
    ->middleware('signed')
    ->name('guest.issues.show');
Route::post('/laporan-pengunjung/{guestIssueReporter}/konfirmasi', [GuestIssueController::class, 'close'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('guest.issues.close');
Route::post('/laporan-pengunjung/{guestIssueReporter}/buka-kembali', [GuestIssueController::class, 'reopen'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('guest.issues.reopen');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.profile');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::put('/account/avatar', [AccountController::class, 'updateAvatar'])->name('account.avatar');
    Route::delete('/account/avatar', [AccountController::class, 'destroyAvatar'])->name('account.avatar.destroy');

    Route::get('/member/dashboard', [DashboardController::class, 'member'])
        ->middleware('role:member')->name('member.dashboard');
    Route::get('/technician/dashboard', [DashboardController::class, 'technician'])
        ->middleware('role:technician,admin,super_admin')->name('technician.dashboard');
    Route::get('/coordinator/dashboard', [DashboardController::class, 'coordinator'])
        ->middleware('role:coordinator,admin,super_admin')->name('coordinator.dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin,super_admin')->name('admin.dashboard');
    Route::get('/superadmin/dashboard', [DashboardController::class, 'superadmin'])
        ->middleware('role:super_admin')->name('superadmin.dashboard');

    Route::livewire('/issues/create', 'issues.create-issue')
        ->middleware('role:member')->name('issues.create');
    Route::livewire('/issues/{issue}', 'issues.show-issue')->name('issues.show');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/users', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
        Route::post('/users', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
        Route::get('/announcements', [App\Http\Controllers\Admin\AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('/announcements', [App\Http\Controllers\Admin\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/announcements/{announcement}', [App\Http\Controllers\Admin\AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [App\Http\Controllers\Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    Route::middleware('superadmin')->prefix('superadmin')->name('superadmin.')->group(function (): void {
        Route::get('/users', [App\Http\Controllers\SuperAdmin\UserController::class, 'index'])->name('users.index');
        Route::get('/roles', [App\Http\Controllers\SuperAdmin\RoleController::class, 'index'])->name('roles.index');
    });
});

require __DIR__.'/member-3.php';
