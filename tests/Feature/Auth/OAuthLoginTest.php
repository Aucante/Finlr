<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Enums\OAuthProvider;
use App\Modules\Auth\Models\OAuthAccount;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;
use Tests\TestCase;

class OAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_route_exists_for_every_supported_provider(): void
    {
        foreach (OAuthProvider::cases() as $provider) {
            $response = $this->get(route('oauth.redirect', ['provider' => $provider->value]));

            $response->assertRedirect();
        }
    }

    public function test_an_unsupported_provider_is_not_found(): void
    {
        $response = $this->get('/auth/facebook/redirect');

        $response->assertNotFound();
    }

    public function test_a_new_user_is_created_and_signed_in_on_first_oauth_login(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-1',
            'email' => 'new-oauth-user@example.com',
            'name' => 'New OAuth User',
            'email_verified' => true,
        ]));

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'new-oauth-user@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);

        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider' => OAuthProvider::GOOGLE->value,
            'provider_id' => 'google-1',
        ]);
    }

    public function test_a_returning_oauth_user_is_signed_in_without_creating_a_duplicate(): void
    {
        $user = User::factory()->create(['email' => 'returning@example.com']);

        OAuthAccount::factory()->for($user)->create([
            'provider' => OAuthProvider::GOOGLE,
            'provider_id' => 'google-42',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-42',
            'email' => 'returning@example.com',
            'name' => 'Returning User',
            'email_verified' => true,
        ]));

        $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'returning@example.com')->count());
        $this->assertSame(1, OAuthAccount::query()->where('user_id', $user->id)->count());
    }

    public function test_a_guest_cannot_take_over_an_existing_account_via_a_matching_oauth_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'shared@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-attacker',
            'email' => 'shared@example.com',
            'name' => 'Attacker Controlled Identity',
            'email_verified' => true,
        ]));

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'shared@example.com')->count());
        $this->assertDatabaseMissing('oauth_accounts', [
            'user_id' => $existingUser->id,
            'provider' => OAuthProvider::GOOGLE->value,
            'provider_id' => 'google-attacker',
        ]);
    }

    public function test_a_user_authenticated_as_someone_else_cannot_link_a_matching_oauth_email_to_a_different_account(): void
    {
        $existingUser = User::factory()->create(['email' => 'shared@example.com']);
        $attacker = User::factory()->create(['email' => 'attacker@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-attacker',
            'email' => 'shared@example.com',
            'name' => 'Attacker Controlled Identity',
            'email_verified' => true,
        ]));

        $response = $this->actingAs($attacker)->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertAuthenticatedAs($attacker);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('oauth_accounts', [
            'user_id' => $existingUser->id,
            'provider' => OAuthProvider::GOOGLE->value,
            'provider_id' => 'google-attacker',
        ]);
        $this->assertSame(0, OAuthAccount::query()->where('user_id', $attacker->id)->count());
    }

    public function test_an_unverified_google_email_is_rejected_before_any_user_lookup(): void
    {
        $existingUser = User::factory()->create(['email' => 'unverified@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-unverified',
            'email' => 'unverified@example.com',
            'name' => 'Unverified Email',
            'email_verified' => false,
        ]));

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'unverified@example.com')->count());
        $this->assertDatabaseMissing('oauth_accounts', [
            'user_id' => $existingUser->id,
            'provider' => OAuthProvider::GOOGLE->value,
            'provider_id' => 'google-unverified',
        ]);
    }

    public function test_a_missing_google_email_verified_claim_is_treated_as_unverified(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-no-claim',
            'email' => 'no-claim@example.com',
            'name' => 'No Claim',
        ]));

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'no-claim@example.com']);
    }

    // Microsoft was removed as a provider (2026-09), which retired the test
    // that used to cover the legitimate linking branch in
    // AuthenticateViaOAuthAction::findOrCreateUser() (Auth::id() === $user->id)
    // by linking a *second* provider to an already-authenticated user's own
    // account. With a single provider, "link a second provider" no longer
    // means anything — but that same guard clause still needs coverage, so
    // this variant keeps it alive via the one scenario that remains
    // meaningful: an already-authenticated user (signed in with a
    // password-only account) linking Google — the only provider — to that
    // same account of theirs.
    public function test_an_authenticated_user_can_link_google_to_their_own_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'existing-password-account@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-self-link',
            'email' => 'existing-password-account@example.com',
            'name' => 'Existing Password Account',
            'email_verified' => true,
        ]));

        $this->actingAs($user)->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('email', 'existing-password-account@example.com')->count());
        $this->assertDatabaseHas('oauth_accounts', [
            'user_id' => $user->id,
            'provider' => OAuthProvider::GOOGLE->value,
            'provider_id' => 'google-self-link',
        ]);
    }

    public function test_an_oauth_provider_failure_redirects_to_login_with_an_error_and_does_not_authenticate(): void
    {
        Socialite::fake('google', function (): never {
            throw new RuntimeException('The provider is unreachable.');
        });

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_a_new_oauth_user_gets_a_remember_me_cookie(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-remember',
            'email' => 'remember-oauth@example.com',
            'name' => 'Remember OAuth',
            'email_verified' => true,
        ]));

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

        $this->assertNotNull($cookie, 'Expected a remember_web_* cookie to be set after OAuth login.');
    }
}
