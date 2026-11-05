<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\Settings\ImpersonationController;
use App\Http\Controllers\Settings\MemberController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    // Admin only; each action is authorized through UserPolicy.
    Route::get('settings/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('settings/members', [MemberController::class, 'store'])->name('members.store');
    Route::patch('settings/members/{user}', [MemberController::class, 'update'])->name('members.update');
    Route::patch('settings/members/{user}/role', [MemberController::class, 'updateRole'])->name('members.role');
    Route::patch('settings/members/{user}/access', [MemberController::class, 'updateAccess'])->name('members.access');
    Route::put('settings/members/{user}/password', [MemberController::class, 'resetPassword'])->name('members.password');
    Route::post('settings/members/{user}/impersonate', [ImpersonationController::class, 'store'])->name('members.impersonate');
    Route::delete('impersonation', [ImpersonationController::class, 'destroy'])->name('impersonation.destroy');

    // Admin only; authorized through SettingPolicy. Update is a POST because it carries file uploads.
    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::post('settings/branding', [BrandingController::class, 'update'])->name('branding.update');
});
