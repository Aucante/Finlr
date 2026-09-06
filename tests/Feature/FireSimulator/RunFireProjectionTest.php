<?php

namespace Tests\Feature\FireSimulator;

use App\Modules\Scenarios\Enums\CalculatorType;
use App\Modules\Scenarios\Models\Scenario;
use App\Modules\SimulationEngine\Contracts\FireEngineInterface;
use App\Modules\Subscriptions\Enums\Plan;
use App\Modules\User\Models\User;
use Composer\InstalledVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RunFireProjectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! InstalledVersions::isInstalled('saucante74/finlr-engine')) {
            $this->markTestSkipped('The private saucante74/finlr-engine package is not installed.');
        }
    }

    public function test_a_free_plan_user_receives_a_403(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::FREE]);

        $response = $this->actingAs($user)->post('/simulators/fire', $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_pro_plan_user_with_valid_data_gets_redirected_to_the_created_scenario(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post('/simulators/fire', $this->validPayload());

        $this->assertDatabaseCount('scenarios', 1);

        $scenario = Scenario::sole();

        $response->assertRedirect(route('scenarios.show', $scenario, absolute: false));
        $this->assertSame($user->id, $scenario->user_id);
        $this->assertSame(CalculatorType::Fire, $scenario->calculator_type);
    }

    public function test_the_stored_input_payload_converts_only_the_return_rate_to_a_fraction(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post('/simulators/fire', $this->validPayload());

        $scenario = Scenario::sole();

        // Submitted as a percentage (6), stored as the fraction the engine
        // actually consumes (docs/API.md §4).
        $this->assertSame(0.06, $scenario->input_payload['annualReturnRate']);
        // withdrawalRate is the one field the package itself expects as a
        // percentage, not a fraction — submitted as 4.5, stored as 4.5
        // untouched.
        $this->assertSame(4.5, $scenario->input_payload['withdrawalRate']);
    }

    public function test_the_stored_result_payload_carries_required_capital_and_the_three_scenarios(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post('/simulators/fire', $this->validPayload());

        $scenario = Scenario::sole();

        $this->assertArrayHasKey('requiredCapital', $scenario->result_payload);
        $this->assertArrayHasKey('optimistic', $scenario->result_payload);
        $this->assertArrayHasKey('neutral', $scenario->result_payload);
        $this->assertArrayHasKey('pessimistic', $scenario->result_payload);
        $this->assertArrayHasKey('requiredCapital', $scenario->result_payload['optimistic']);
    }

    public function test_a_scenario_submitted_without_a_name_is_rejected(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $payload = $this->validPayload();
        unset($payload['name']);

        $response = $this->actingAs($user)->post('/simulators/fire', $payload);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_scenario_created_with_a_name_stores_it_as_is(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post(
            '/simulators/fire',
            array_merge($this->validPayload(), ['name' => 'Indépendance à 55 ans']),
        );

        $scenario = Scenario::sole();

        $this->assertSame('Indépendance à 55 ans', $scenario->name);
    }

    public function test_a_pro_plan_user_with_missing_data_gets_validation_errors_and_no_scenario_is_created(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);
        $payload = $this->validPayload();
        unset($payload['currentAge']);

        $response = $this->actingAs($user)->post('/simulators/fire', $payload);

        $response->assertSessionHasErrors('currentAge');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_an_invariant_violation_the_form_does_not_pre_validate_redirects_with_a_flashed_error(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        // negative currentAge: the FormRequest only checks it is an
        // integer, not that it is >= 0 (docs/API.md §4) — the package's own
        // InvalidFireProjectionInput is expected to surface here instead.
        $response = $this->actingAs($user)->post(
            '/simulators/fire',
            array_merge($this->validPayload(), ['currentAge' => -1]),
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('simulation');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_container_resolution_failure_redirects_with_a_flashed_error_instead_of_a_500(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->app->bind(FireEngineInterface::class, function (): never {
            throw new RuntimeException('The private saucante74/finlr-engine package is not installed.');
        });

        $response = $this->actingAs($user)->post('/simulators/fire', $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHasErrors('simulation');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_the_eleventh_request_within_a_minute_receives_a_429(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);
        $this->actingAs($user);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/simulators/fire', $this->validPayload());
            $response->assertRedirect();
        }

        $eleventh = $this->post('/simulators/fire', $this->validPayload());

        $eleventh->assertStatus(429);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Indépendance à 55 ans',
            'currentAge' => 30,
            'currentCapital' => 10_000,
            'monthlyContribution' => 500,
            'annualReturnRate' => 6.0,
            'desiredAnnualIncome' => 20_000,
            'withdrawalRate' => 4.5,
        ];
    }
}
