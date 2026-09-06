<?php

namespace Tests\Feature\AnalogySimulator;

use App\Modules\Scenarios\Enums\CalculatorType;
use App\Modules\Scenarios\Models\Scenario;
use App\Modules\SimulationEngine\Contracts\AnalogyEngineInterface;
use App\Modules\Subscriptions\Enums\Plan;
use App\Modules\User\Models\User;
use Composer\InstalledVersions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RunAnalogyComparisonTest extends TestCase
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

        $response = $this->actingAs($user)->post('/simulators/analogy', $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_pro_plan_user_with_valid_data_gets_redirected_to_the_created_scenario(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post('/simulators/analogy', $this->validPayload());

        $this->assertDatabaseCount('scenarios', 1);

        $scenario = Scenario::sole();

        $response->assertRedirect(route('scenarios.show', $scenario, absolute: false));
        $this->assertSame($user->id, $scenario->user_id);
        $this->assertSame(CalculatorType::Analogy, $scenario->calculator_type);
    }

    public function test_labels_given_by_the_user_are_used_as_is(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post('/simulators/analogy', array_merge($this->validPayload(), [
            'labelA' => 'Mon PEA',
            'labelB' => 'Mon CTO',
        ]));

        $scenario = Scenario::sole();

        $this->assertSame('Mon PEA', $scenario->input_payload['labelA']);
        $this->assertSame('Mon CTO', $scenario->input_payload['labelB']);
        $this->assertSame('Mon PEA', $scenario->result_payload['labelA']);
        $this->assertSame('Mon CTO', $scenario->result_payload['labelB']);
    }

    public function test_omitted_labels_resolve_to_the_translated_defaults(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);
        $payload = $this->validPayload();
        unset($payload['labelA'], $payload['labelB']);

        $this->actingAs($user)->post('/simulators/analogy', $payload);

        $scenario = Scenario::sole();

        $this->assertSame(__('simulator.analogy.defaultLabelA'), $scenario->input_payload['labelA']);
        $this->assertSame(__('simulator.analogy.defaultLabelB'), $scenario->input_payload['labelB']);
        // Guards against the regression this exact test used to miss: both
        // sides above call the same __() helper, so they'd match each
        // other even while both silently returned the raw key string
        // (lang/{locale}/simulator.php missing). Pin the actual resolved
        // French string as well.
        $this->assertSame('Scénario A', $scenario->input_payload['labelA']);
        $this->assertSame('Scénario B', $scenario->input_payload['labelB']);
    }

    public function test_the_stored_input_payload_mirrors_the_submitted_scenarios(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post('/simulators/analogy', $this->validPayload());

        $scenario = Scenario::sole();

        $this->assertSame('PEA', $scenario->input_payload['accountTypeA']);
        $this->assertSame('CTO', $scenario->input_payload['accountTypeB']);
        // Submitted as a percentage (6), stored as the fraction the engine
        // actually consumes (docs/API.md §2).
        $this->assertSame(0.06, $scenario->input_payload['annualReturnRate']);
    }

    public function test_the_stored_result_payload_carries_the_deltas_and_final_leader(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post('/simulators/analogy', $this->validPayload());

        $scenario = Scenario::sole();

        $this->assertArrayHasKey('realNetBalanceWithInflation', $scenario->result_payload);
        $this->assertArrayHasKey('absolute', $scenario->result_payload['realNetBalanceWithInflation']);
        $this->assertContains($scenario->result_payload['finalLeader'], ['SCENARIO_A', 'SCENARIO_B', 'TIE']);
        $this->assertCount(20, $scenario->result_payload['yearlyBreakdown']);
    }

    public function test_a_scenario_submitted_without_a_name_is_rejected(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $payload = $this->validPayload();
        unset($payload['name']);

        $response = $this->actingAs($user)->post('/simulators/analogy', $payload);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_scenario_created_with_a_name_stores_it_as_is(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->actingAs($user)->post(
            '/simulators/analogy',
            array_merge($this->validPayload(), ['name' => 'PEA vs CTO à 20 ans']),
        );

        $scenario = Scenario::sole();

        $this->assertSame('PEA vs CTO à 20 ans', $scenario->name);
    }

    public function test_a_pro_plan_user_with_invalid_data_gets_validation_errors_and_no_scenario_is_created(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post(
            '/simulators/analogy',
            array_merge($this->validPayload(), ['durationYears' => -1]),
        );

        $response->assertSessionHasErrors('durationYears');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_an_unknown_account_type_is_rejected(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $response = $this->actingAs($user)->post(
            '/simulators/analogy',
            array_merge($this->validPayload(), ['accountTypeA' => 'NOT_A_REAL_ACCOUNT_TYPE']),
        );

        $response->assertSessionHasErrors('accountTypeA');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_a_container_resolution_failure_redirects_with_a_flashed_error_instead_of_a_500(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);

        $this->app->bind(AnalogyEngineInterface::class, function (): never {
            throw new RuntimeException('The private saucante74/finlr-engine package is not installed.');
        });

        $response = $this->actingAs($user)->post('/simulators/analogy', $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHasErrors('simulation');
        $this->assertDatabaseCount('scenarios', 0);
    }

    public function test_the_eleventh_request_within_a_minute_receives_a_429(): void
    {
        $user = User::factory()->create(['subscription_plan' => Plan::PRO_MONTHLY]);
        $this->actingAs($user);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/simulators/analogy', $this->validPayload());
            $response->assertRedirect();
        }

        $eleventh = $this->post('/simulators/analogy', $this->validPayload());

        $eleventh->assertStatus(429);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'name' => 'PEA vs CTO à 20 ans',
            'accountTypeA' => 'PEA',
            'accountTypeB' => 'CTO',
            'initialAmount' => 0,
            'monthlyContribution' => 1000,
            'durationYears' => 20,
            'annualReturnRate' => 6.0,
            'managementFeeRate' => 0.0,
            'inflationRate' => 2.0,
        ];
    }
}
