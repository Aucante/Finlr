<?php

namespace Tests\Feature\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorChallengeScreenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `withSession()` seeds the *next* request's session with these exact
     * values — plain ISO-8601 strings, not Carbon instances — matching
     * exactly what any real, persistent session driver hands back after a
     * write/read round-trip through config/session.php's
     * `serialization => 'json'` (Carbon serializes to an ISO-8601 string
     * under json_encode(); Laravel's Store does not rehydrate it back into
     * Carbon on read). phpunit.xml's `SESSION_DRIVER=array` never
     * serializes attributes at all, so a plain `$this->post('/login')`
     * followed by `$this->get(...)` in this same test suite cannot
     * reproduce this: the Carbon instance survives in memory untouched.
     * This is the exact condition ResolvePendingTwoFactorUserAction must
     * survive, confirmed via a live reproduction in a real browser against
     * the real `database` driver (see RAPPORT.md).
     */
    public function test_the_challenge_screen_is_shown_right_after_a_correct_password_on_a_two_factor_account(): void
    {
        $user = User::factory()->twoFactorEnabled()->create();

        $response = $this->withSession([
            'two_factor.pending_user_id' => $user->id,
            'two_factor.pending_started_at' => now()->toJSON(),
            'two_factor.expires_at' => now()->addMinutes(10)->toJSON(),
        ])->get(route('two-factor.challenge'));

        $response->assertOk();
        $response->assertSessionHasNoErrors();
    }
}
