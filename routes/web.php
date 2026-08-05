<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::view('/reset-password/{token}', 'auth.reset-password')->name('password.reset');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

/**
 * RBAC: only 'admin' and 'user' roles exist. Both can write; the
 * verification queue is admin-only, enforced in
 * AdminVerificationQueue::mount().
 */
Route::middleware(['auth'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::view('/assets', 'assets.index')->name('assets.index');
    Route::view('/risks', 'risks.index')->name('risks.index');
    Route::view('/changes', 'changes.index')->name('changes.index');
    Route::view('/data-information', 'data-information.index')->name('data-information.index');
    Route::view('/hr-risks', 'hr-risks.index')->name('hr-risks.index');
    Route::view('/knowledge', 'knowledge.index')->name('knowledge.index');
    Route::view('/services', 'services.index')->name('services.index');
    Route::view('/security-programs', 'security-programs.index')->name('security-programs.index');

    Route::view('/profile', 'profile.edit')->name('profile.edit');
    Route::view('/panduan', 'guide.index')->name('guide.index');
});
