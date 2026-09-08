<?php

namespace Tests\Feature;

use App\Modules\Subscriptions\Enums\Permission;
use App\Modules\Subscriptions\Enums\Plan;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SubscriptionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_only_has_the_create_project_permission(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::FREE]);

        $this->assertTrue(Gate::forUser($user)->allows(Permission::CREATE_PROJECT->value));
        $this->assertFalse(Gate::forUser($user)->allows(Permission::EXPORT_REPORTS->value));
        $this->assertFalse(Gate::forUser($user)->allows(Permission::ADVANCED_CALCULATOR->value));
    }

    public function test_pro_monthly_user_has_all_permissions(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->assertTrue(Gate::forUser($user)->allows(Permission::CREATE_PROJECT->value));
        $this->assertTrue(Gate::forUser($user)->allows(Permission::EXPORT_REPORTS->value));
        $this->assertTrue(Gate::forUser($user)->allows(Permission::ADVANCED_CALCULATOR->value));
    }

    public function test_user_has_permission_helper_matches_the_gates(): void
    {
        $free = User::factory()->create(['subscription_plan' => Plan::FREE]);
        $pro = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->assertTrue($free->hasPermission(Permission::CREATE_PROJECT));
        $this->assertFalse($free->hasPermission(Permission::EXPORT_REPORTS));
        $this->assertTrue($pro->hasPermission(Permission::EXPORT_REPORTS));
        $this->assertTrue($pro->hasPermission(Permission::ADVANCED_CALCULATOR));
    }

    public function test_subscription_plan_is_cast_to_the_plan_enum(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_YEARLY]);

        $this->assertInstanceOf(Plan::class, $user->subscription_plan);
        $this->assertSame(Plan::PRO_YEARLY, $user->subscription_plan);
    }

    public function test_plan_grants_the_correct_permissions(): void
    {
        $this->assertTrue(Plan::FREE->grants(Permission::CREATE_PROJECT));
        $this->assertFalse(Plan::FREE->grants(Permission::EXPORT_REPORTS));
        $this->assertFalse(Plan::FREE->grants(Permission::ADVANCED_CALCULATOR));

        foreach ([Plan::PRO_MONTHLY, Plan::PRO_YEARLY, Plan::ENTERPRISE] as $paidPlan) {
            $this->assertTrue($paidPlan->grants(Permission::CREATE_PROJECT));
            $this->assertTrue($paidPlan->grants(Permission::EXPORT_REPORTS));
            $this->assertTrue($paidPlan->grants(Permission::ADVANCED_CALCULATOR));
        }
    }

    public function test_plan_is_paid_and_max_projects_allowed(): void
    {
        $this->assertFalse(Plan::FREE->isPaid());
        $this->assertTrue(Plan::PRO_MONTHLY->isPaid());
        $this->assertTrue(Plan::PRO_YEARLY->isPaid());
        $this->assertTrue(Plan::ENTERPRISE->isPaid());

        $this->assertSame(1, Plan::FREE->maxProjectsAllowed());
        $this->assertSame(10, Plan::PRO_MONTHLY->maxProjectsAllowed());
        $this->assertSame(10, Plan::PRO_YEARLY->maxProjectsAllowed());
        $this->assertSame(PHP_INT_MAX, Plan::ENTERPRISE->maxProjectsAllowed());
    }

    public function test_free_and_pro_monthly_users_share_the_correct_permissions_via_inertia(): void
    {
        $free = User::factory()->create(['subscription_plan' => Plan::FREE]);
        $pro = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($free)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('auth.plan', 'free')
                ->where('auth.permissions', ['create_project'])
            );

        $this->actingAs($pro)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('auth.plan', 'pro_monthly')
                ->where('auth.permissions', [
                    'export_reports',
                    'create_project',
                    'advanced_calculator',
                ])
            );
    }
}
