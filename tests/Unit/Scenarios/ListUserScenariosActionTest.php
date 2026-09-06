<?php

namespace Tests\Unit\Scenarios;

use App\Modules\Scenarios\Actions\ListUserScenariosAction;
use App\Modules\Scenarios\Models\Scenario;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

class ListUserScenariosActionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Restore Laravel's default (request-driven) page resolver — this
        // test class overrides it in test_it_returns_the_remaining_scenario_on_the_second_page,
        // and Paginator::currentPageResolver is a shared static, so it would
        // otherwise leak into every later test in this process.
        Paginator::currentPageResolver(fn (string $pageName = 'page'): int => (int) (request()->input($pageName, 1)));

        parent::tearDown();
    }

    public function test_it_only_returns_scenarios_belonging_to_the_given_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Scenario::factory()->count(2)->create(['user_id' => $owner->id]);
        Scenario::factory()->count(3)->create(['user_id' => $other->id]);

        $action = new ListUserScenariosAction;
        $page = $action->handle($owner);

        $this->assertCount(2, $page);
        $this->assertSame(2, $page->total());
    }

    public function test_it_sorts_scenarios_from_most_recent_to_oldest(): void
    {
        $user = User::factory()->create();

        $oldest = Scenario::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(2)]);
        $newest = Scenario::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
        $middle = Scenario::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDay()]);

        $action = new ListUserScenariosAction;
        $page = $action->handle($user);

        $this->assertSame(
            [$newest->id, $middle->id, $oldest->id],
            array_map(fn (array $summary): int => $summary['id'], $page->items()),
        );
    }

    public function test_it_limits_a_page_to_ten_scenarios(): void
    {
        $user = User::factory()->create();
        Scenario::factory()->count(11)->create(['user_id' => $user->id]);

        $action = new ListUserScenariosAction;
        $page = $action->handle($user);

        $this->assertCount(10, $page);
        $this->assertSame(11, $page->total());
        $this->assertSame(2, $page->lastPage());
    }

    public function test_it_returns_the_remaining_scenario_on_the_second_page(): void
    {
        $user = User::factory()->create();
        Scenario::factory()->count(11)->create(['user_id' => $user->id]);

        Paginator::currentPageResolver(fn (): int => 2);

        $action = new ListUserScenariosAction;
        $page = $action->handle($user);

        $this->assertCount(1, $page);
        $this->assertSame(2, $page->currentPage());
    }

    public function test_each_item_is_serialised_like_scenario_summary_data(): void
    {
        $user = User::factory()->create();
        Scenario::factory()->create(['user_id' => $user->id, 'name' => 'Retraite à 62 ans']);

        $action = new ListUserScenariosAction;
        $page = $action->handle($user);

        $this->assertIsArray($page->items()[0]);
        $this->assertSame('Retraite à 62 ans', $page->items()[0]['name']);
        $this->assertArrayHasKey('typeLabel', $page->items()[0]);
    }
}
