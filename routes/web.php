<?php

use App\Http\Controllers\ProfileInformationController;
use App\Http\Controllers\TwoFactorSettingsController;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'dashboard')->middleware('verified')->name('dashboard');
    Route::get('/account/profile', [ProfileInformationController::class, 'edit'])->name('account.profile.edit');
    Route::get('/account/password', function (): View {
        abort_unless(Features::enabled(Features::updatePasswords()), 404);

        return view('auth.password.edit');
    })->name('account.password.edit');
    Route::middleware('password.confirm')->prefix('/account/security')->name('two-factor.')->group(function (): void {
        Route::get('/', [TwoFactorSettingsController::class, 'show'])->name('settings');
        Route::get('/recovery-codes', [TwoFactorSettingsController::class, 'recovery'])->name('recovery');
    });
});
