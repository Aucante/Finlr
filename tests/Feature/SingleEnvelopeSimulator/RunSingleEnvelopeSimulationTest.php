<?php

namespace Tests\Feature\SingleEnvelopeSimulator;

use App\Modules\Scenarios\Enums\CalculatorType;
use App\Modules\Scenarios\Models\Scenario;
use App\Modules\SimulationEngine\Contracts\SimulationEngineInterface;
use App\Modules\Subscriptions\Enums\Plan;
use App\Modules\User\Models\User;
use Composer\InstalledVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RunSingleEnvelopeSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! InstalledVersions::isInstalled('saucante74/finlr-engine')) {
            $this->markTestSkipped('The private saucante74/finlr-engine package is not installed.');
        }
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $response = $this->post('/simulators/single-envelope/france/pea', $this->validPayload());

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_free_plan_user_receives_a_403(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::FREE]);

        $response = $this->actingAs($user)->post('/simulators/single-envelope/france/pea', $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_pro_plan_user_with_valid_data_gets_redirected_to_the_created_scenario(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post('/simulators/single-envelope/france/pea', $this->validPayload());

        $this->assertDatabaseCount('scenarios', 1);

        $scenario = Scenario::sole();

        $response->assertRedirect(route('scenarios.show', $scenario, absolute: false));
        $this->assertSame($user->id, $scenario->user_id);
        $this->assertSame(CalculatorType::SingleEnvelope, $scenario->calculator_type);
    }

    public function test_a_scenario_submitted_without_a_name_is_rejected(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $payload = $this->validPayload();
        unset($payload['name']);

        $response = $this->actingAs($user)->post('/simulators/single-envelope/france/pea', $payload);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_scenario_created_with_a_name_stores_it_as_is(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post(
            '/simulators/single-envelope/france/pea',
            array_merge($this->validPayload(), ['name' => 'Retraite à 62 ans']),
        );

        $scenario = Scenario::sole();

        $this->assertSame('Retraite à 62 ans', $scenario->name);
    }

    public function test_a_pro_plan_user_with_invalid_data_gets_validation_errors_and_no_scenario_is_created(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post(
            '/simulators/single-envelope/france/pea',
            array_merge($this->validPayload(), ['years' => -1]),
        );

        $response->assertSessionHasErrors('years');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_post_to_a_wrapper_outside_the_enum_receives_a_404(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        // The wrapper now lives in the URL, so an unknown one is a wrong URL
        // (404 via implicit enum binding), not a validation error on a field.
        $response = $this->actingAs($user)->post(
            '/simulators/single-envelope/france/av',
            $this->validPayload(),
        );

        $response->assertNotFound();
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_wrapper_forged_in_the_body_cannot_override_the_one_from_the_url(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post(
            '/simulators/single-envelope/france/pea',
            array_merge($this->validPayload(), ['wrapper' => 'cto']),
        );

        $scenario = Scenario::sole();

        $this->assertSame('pea', $scenario->input_payload['wrapper']);
    }

    public function test_a_container_resolution_failure_redirects_with_a_flashed_error_instead_of_a_500(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        // Simulates SimulationEngineServiceProvider's own failure mode (the
        // private finlr-engine package missing) without depending on it
        // actually being absent from this environment.
        $this->app->bind(SimulationEngineInterface::class, function (): never {
            throw new RuntimeException('The private saucante74/finlr-engine package is not installed.');
        });

        $response = $this->actingAs($user)->post('/simulators/single-envelope/france/pea', $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHasErrors('simulation');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_the_eleventh_request_within_a_minute_receives_a_429(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);
        $this->actingAs($user);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/simulators/single-envelope/france/pea', $this->validPayload());
            $response->assertRedirect();
        }

        $eleventh = $this->post('/simulators/single-envelope/france/pea', $this->validPayload());

        $eleventh->assertStatus(429);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Retraite à 62 ans',
            'initialCapital' => 1000,
            'monthlyContribution' => 200,
            'annualRate' => 5.5,
            'years' => 10,
            'wrapperFee' => 0.6,
            'fundFee' => 0.3,
            'taxRate' => 12.8,
            'inflationRate' => 2.0,
            'inflationEnabled' => true,
        ];
    }
}
