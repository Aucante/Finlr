<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Actions\AuthenticateViaOAuthAction;
use App\Modules\Auth\DTOs\OAuthUserData;
use App\Modules\Auth\Enums\OAuthProvider;
use App\Modules\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class HandleOAuthCallbackController extends Controller
{
    public function __invoke(OAuthProvider $provider, AuthenticateViaOAuthAction $action): RedirectResponse
    {
        try {
            $socialiteUser = Socialite::driver($provider->value)->user();

            $action->handle($provider, OAuthUserData::fromSocialiteUser($provider, $socialiteUser));
        } catch (ValidationException $exception) {
            // Not a bug — an expected rejection (e.g. the account-linking
            // guard in AuthenticateViaOAuthAction) with its own explicit,
            // translated message, so it is redirected as-is instead of
            // being swallowed by the generic handler below.
            return Redirect::route('login')->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return Redirect::route('login')->withErrors([
                'email' => trans('auth.oauth_failed'),
            ]);
        }

        return Redirect::intended(route('dashboard', absolute: false));
    }
}
