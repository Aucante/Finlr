<?php

namespace App\Modules\Scenarios\Actions;

use App\Modules\Scenarios\DTOs\ScenarioSummaryData;
use App\Modules\Scenarios\Models\Scenario;
use App\Modules\User\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ListUserScenariosAction
{
    private const PER_PAGE = 10;

    public function handle(User $user): LengthAwarePaginator
    {
        return Scenario::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(self::PER_PAGE)
            ->through(fn (Scenario $scenario): array => ScenarioSummaryData::fromModel($scenario)->toArray());
    }
}
