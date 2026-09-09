<?php

use App\Modules\Auth\Controllers\DestroySessionController;
use App\Modules\Auth\Controllers\HandleOAuthCallbackController;
use App\Modules\Auth\Controllers\RedirectToOAuthProviderController;
use App\Modules\Auth\Controllers\ResendTwoFactorCodeController;
use App\Modules\Auth\Controllers\ResetPasswordController;
use App\Modules\Auth\Controllers\SendEmailVerificationNotificationController;
use App\Modules\Auth\Controllers\SendPasswordResetLinkController;
use App\Modules\Auth\Controllers\ShowConfirmPasswordFormController;
use App\Modules\Auth\Controllers\ShowForgotPasswordFormController;
use App\Modules\Auth\Controllers\ShowLoginFormController;
use App\Modules\Auth\Controllers\ShowRegistrationFormController;
use App\Modules\Auth\Controllers\ShowResetPasswordFormController;
use App\Modules\Auth\Controllers\ShowTwoFactorChallengeFormController;
use App\Modules\Auth\Controllers\ShowVerifyEmailPromptController;
use App\Modules\Auth\Controllers\StoreConfirmedPasswordController;
use App\Modules\Auth\Controllers\StoreSessionController;
use App\Modules\Auth\Controllers\StoreUserController;
use App\Modules\Auth\Controllers\UpdatePasswordController;
use App\Modules\Auth\Controllers\VerifyTwoFactorCodeController;
use App\Modules\Auth\Controllers\VerifyUserEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', ShowRegistrationFormController::class)
        ->name('register');

    Route::post('register', StoreUserController::class)
        ->middleware('throttle:register');

    Route::get('login', ShowLoginFormController::class)
        ->name('login');

    Route::post('login', StoreSessionController::class);

    // Reachable while Auth::check() is false — a pending 2FA login never
    // calls Auth::login() (CONCEPTION.md, section 3), so `guest`
    // middleware is correct here even though the user already gave a
    // valid password.
    Route::get('two-factor-challenge', ShowTwoFactorChallengeFormController::class)
        ->name('two-factor.challenge');

    Route::post('two-factor-challenge', VerifyTwoFactorCodeController::class)
        ->middleware('throttle:two-factor-verify')
        ->name('two-factor.verify');

    Route::post('two-factor-challenge/resend', ResendTwoFactorCodeController::class)
        ->middleware('throttle:two-factor-resend')
        ->name('two-factor.resend');

    Route::get('forgot-password', ShowForgotPasswordFormController::class)
        ->name('password.request');

    Route::post('forgot-password', SendPasswordResetLinkController::class)
        ->middleware('throttle:forgot-password')
        ->name('password.email');

    Route::get('reset-password/{token}', ShowResetPasswordFormController::class)
        ->name('password.reset');

    Route::post('reset-password', ResetPasswordController::class)
        ->middleware('throttle:reset-password')
        ->name('password.store');
});

// Not gated behind `guest`: an already-authenticated user must be able to
// link an additional provider to their account (see AuthenticateViaOAuthAction).
Route::middleware('throttle:oauth')->group(function () {
    Route::get('auth/{provider}/redirect', RedirectToOAuthProviderController::class)
        ->name('oauth.redirect');

    Route::get('auth/{provider}/callback', HandleOAuthCallbackController::class)
        ->name('oauth.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', ShowVerifyEmailPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyUserEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', SendEmailVerificationNotificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', ShowConfirmPasswordFormController::class)
        ->name('password.confirm');

    Route::post('confirm-password', StoreConfirmedPasswordController::class);

    Route::put('password', UpdatePasswordController::class)->name('password.update');

    Route::post('logout', DestroySessionController::class)
        ->name('logout');
});
