<?php

namespace Tests\Feature\MultiEnvelopeSimulator;

use App\Modules\Scenarios\Enums\CalculatorType;
use App\Modules\Scenarios\Models\Scenario;
use App\Modules\SimulationEngine\Contracts\MultiEnvelopeEngineInterface;
use App\Modules\Subscriptions\Enums\Plan;
use App\Modules\User\Models\User;
use Composer\InstalledVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RunMultiEnvelopeSimulationTest extends TestCase
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
        $response = $this->post('/simulators/multi-envelope', $this->validPayload());

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_free_plan_user_receives_a_403(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::FREE]);

        $response = $this->actingAs($user)->post('/simulators/multi-envelope', $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_pro_plan_user_with_valid_data_gets_redirected_to_the_created_scenario(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post('/simulators/multi-envelope', $this->validPayload());

        $this->assertDatabaseCount('scenarios', 1);

        $scenario = Scenario::sole();

        $response->assertRedirect(route('scenarios.show', $scenario, absolute: false));
        $this->assertSame($user->id, $scenario->user_id);
        $this->assertSame(CalculatorType::MultiEnvelope, $scenario->calculator_type);
    }

    public function test_the_stored_input_payload_mirrors_the_submitted_cascade(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post('/simulators/multi-envelope', $this->validPayload());

        $scenario = Scenario::sole();

        $this->assertCount(2, $scenario->input_payload['envelopes']);
        $this->assertSame('PEA', $scenario->input_payload['envelopes'][0]['accountType']);
        $this->assertSame('CTO', $scenario->input_payload['envelopes'][1]['accountType']);
        // Submitted as a percentage (5), stored as the fraction the engine
        // actually consumes (docs/API.md §2).
        $this->assertSame(0.05, $scenario->input_payload['envelopes'][0]['annualReturnRate']);
    }

    public function test_the_stored_result_payload_has_one_pocket_per_envelope(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post('/simulators/multi-envelope', $this->validPayload());

        $scenario = Scenario::sole();

        $this->assertCount(2, $scenario->result_payload['pockets']);
    }

    public function test_a_scenario_submitted_without_a_name_is_rejected(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $payload = $this->validPayload();
        unset($payload['name']);

        $response = $this->actingAs($user)->post('/simulators/multi-envelope', $payload);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_scenario_created_with_a_name_stores_it_as_is(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post(
            '/simulators/multi-envelope',
            array_merge($this->validPayload(), ['name' => 'Cascade PEA + CTO']),
        );

        $scenario = Scenario::sole();

        $this->assertSame('Cascade PEA + CTO', $scenario->name);
    }

    public function test_a_pro_plan_user_with_invalid_data_gets_validation_errors_and_no_scenario_is_created(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $payload = $this->validPayload();
        $payload['envelopes'][0]['durationYears'] = -1;

        $response = $this->actingAs($user)->post('/simulators/multi-envelope', $payload);

        $response->assertSessionHasErrors('envelopes.0.durationYears');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_an_empty_envelopes_array_is_rejected(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post(
            '/simulators/multi-envelope',
            array_merge($this->validPayload(), ['envelopes' => []]),
        );

        $response->assertSessionHasErrors('envelopes');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_an_unknown_account_type_is_rejected(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $payload = $this->validPayload();
        $payload['envelopes'][0]['accountType'] = 'NOT_A_REAL_ACCOUNT_TYPE';

        $response = $this->actingAs($user)->post('/simulators/multi-envelope', $payload);

        $response->assertSessionHasErrors('envelopes.0.accountType');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_container_resolution_failure_redirects_with_a_flashed_error_instead_of_a_500(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->app->bind(MultiEnvelopeEngineInterface::class, function (): never {
            throw new RuntimeException('The private saucante74/finlr-engine package is not installed.');
        });

        $response = $this->actingAs($user)->post('/simulators/multi-envelope', $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHasErrors('simulation');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_the_eleventh_request_within_a_minute_receives_a_429(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);
        $this->actingAs($user);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/simulators/multi-envelope', $this->validPayload());
            $response->assertRedirect();
        }

        $eleventh = $this->post('/simulators/multi-envelope', $this->validPayload());

        $eleventh->assertStatus(429);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Cascade PEA + CTO',
            'inflationRate' => 2.0,
            'envelopes' => [
                [
                    'accountType' => 'PEA',
                    'initialAmount' => 1000,
                    'monthlyContribution' => 200,
                    'durationYears' => 10,
                    'annualReturnRate' => 5.0,
                    'managementFeeRate' => 0.5,
                ],
                [
                    'accountType' => 'CTO',
                    'initialAmount' => 0,
                    'monthlyContribution' => 100,
                    'durationYears' => 10,
                    'annualReturnRate' => 4.0,
                    'managementFeeRate' => 0.3,
                ],
            ],
        ];
    }
}
