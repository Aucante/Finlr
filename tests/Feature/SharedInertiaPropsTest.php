<?php

namespace Tests\Feature;

use App\Modules\Subscriptions\Enums\Plan;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SharedInertiaPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shared_auth_user_exposes_only_the_whitelisted_fields(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                // The scoped assertion fails if auth.user carries any extra key,
                // so this locks the payload down to exactly these five fields.
                ->has('auth.user', fn (Assert $sharedUser) => $sharedUser
                    ->where('id', $user->id)
                    ->where('name', $user->name)
                    ->where('email', $user->email)
                    ->where('email_verified_at', $user->email_verified_at?->toJSON())
                    // Derived boolean only — see the test below for what
                    // must never accompany it (the raw timestamp, or any
                    // data from the separate two_factor_codes /
                    // two_factor_trusted_devices tables).
                    ->where('two_factor_enabled', false)
                )
            );
    }

    public function test_the_shared_auth_user_never_leaks_the_password_or_the_subscription_plan(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn (Assert $page) => $page
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token')
            ->missing('auth.user.subscription_plan')
            ->missing('auth.user.created_at')
            ->missing('auth.user.updated_at')
            // The raw timestamp never leaks, only the derived boolean above.
            ->missing('auth.user.two_factor_enabled_at')
            ->etc()
        );

        // The plan itself stays available, but only through its dedicated prop.
        $response->assertInertia(fn (Assert $page) => $page
            ->where('auth.plan', 'pro_monthly')
            ->etc()
        );
    }

    public function test_the_public_calculator_page_shares_a_null_user_for_guests(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user', null)
                ->where('auth.permissions', [])
                ->etc()
            );
    }
}
