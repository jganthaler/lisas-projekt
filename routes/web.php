<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\VerifyEmailController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\ExpiringItems;
use App\Livewire\Freezer\ClosedFreezer;
use App\Livewire\Freezer\DrawerPage;
use App\Livewire\Freezer\OpenFreezer;
use App\Livewire\InventoryOverview;
use App\Livewire\Invitations\AcceptInvitation;
use App\Livewire\Profile\ProfilePage;
use App\Livewire\Sharing\SharingPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/freezer');

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
| The password reset lives here as well: asking for the link and choosing the
| new password are both only interesting for visitors without a session.
*/
Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/register', Register::class)->name('register');

    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');

    // The name "password.reset" is the one Laravel's reset notification builds
    // its link with; the address of the account travels in the query string.
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

/*
|--------------------------------------------------------------------------
| E-mail verification
|--------------------------------------------------------------------------
| An account that has not confirmed its address may only reach these routes:
| the reminder with the "send again" button and the signed link from the mail.
| Logging out has to stay possible as well.
*/
Route::middleware('auth')->group(function () {
    Route::livewire('/email/verify', VerifyEmail::class)->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/logout', LogoutController::class)->name('logout');
});

/*
|--------------------------------------------------------------------------
| The app (authenticated and verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/freezer', ClosedFreezer::class)->name('freezer.closed');
    Route::livewire('/freezer/open', OpenFreezer::class)->name('freezer.open');
    Route::livewire('/freezer/drawer/{drawer}', DrawerPage::class)->name('drawers.show');
    Route::livewire('/inventory', InventoryOverview::class)->name('inventory.index');
    Route::livewire('/expiring', ExpiringItems::class)->name('expiring.index');
    Route::livewire('/sharing', SharingPage::class)->name('sharing.index');
    Route::livewire('/profile', ProfilePage::class)->name('profile.edit');

    Route::get('/avatars/{user}', AvatarController::class)->name('avatars.show');
});

/*
|--------------------------------------------------------------------------
| Invitations
|--------------------------------------------------------------------------
| Guests are redirected to the login screen by the "auth" middleware - Laravel
| remembers the intended URL, so the invitation is continued right after the
| login (or registration). An unverified account is sent to the reminder page
| first and lands back on the invitation after the confirmation, which is why
| the route is not part of the guest group.
*/
Route::livewire('/invitations/{token}', AcceptInvitation::class)
    ->middleware(['auth', 'verified'])
    ->name('invitations.accept');
