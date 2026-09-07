<?php

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\DTOs\OAuthUserData;
use App\Modules\Auth\Enums\OAuthProvider;
use App\Modules\Auth\Models\OAuthAccount;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticateViaOAuthAction
{
    public function handle(OAuthProvider $provider, OAuthUserData $data): User
    {
        $account = OAuthAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $data->providerId)
            ->first();

        $user = $account !== null ? $account->user()->firstOrFail() : $this->findOrCreateUser($provider, $data);

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, remember: true);

        return $user;
    }

    private function findOrCreateUser(OAuthProvider $provider, OAuthUserData $data): User
    {
        $user = User::query()->where('email', $data->email)->first();

        // A match by email must never auto-link: without this check, anyone
        // controlling an OAuth identity for the victim's email address
        // could take over their existing Finlr account without ever
        // knowing the password. Linking is only legitimate when the
        // current session already belongs to that same account (adding a
        // second provider); Auth::id() is null for a guest, which is
        // correctly rejected here too.
        if ($user !== null && Auth::id() !== $user->id) {
            throw ValidationException::withMessages([
                'email' => trans('auth.oauth_account_exists'),
            ]);
        }

        // No password: this account can only ever be reached through an
        // OAuth provider, never through the email/password form.
        $user ??= User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => null,
        ]);

        $user->oauthAccounts()->create([
            'provider' => $provider,
            'provider_id' => $data->providerId,
        ]);

        return $user;
    }
}
