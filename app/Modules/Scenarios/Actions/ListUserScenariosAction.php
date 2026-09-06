<?php

namespace App\Modules\Scenarios\Actions;

use App\Modules\Scenarios\DTOs\ScenarioSummaryData;
use App\Modules\Scenarios\Models\Scenario;
use App\Modules\User\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ListUserScenariosAction
{
    private const PER_PAGE = 10;

    /**
     * TValue on LengthAwarePaginator is invariant (not covariant), and
     * PaginatedData::fromPaginator() — the shared envelope every paginated
     * Action feeds into — declares its parameter as
     * LengthAwarePaginator<int, array<string, mixed>>. Annotating this method
     * with the exact shape of ScenarioSummaryData::toArray() therefore breaks
     * at that call site (invariance rejects the narrower type), which is why
     * an earlier pass left this return type unannotated instead.
     *
     * Widening the annotation to array<string, mixed> alone does not fix it
     * either: PHPStan resolves through()'s TMapValue from the *inferred*
     * return type of the callback expression, not from a PHPDoc placed on an
     * inline closure/arrow function, so it still reports the exact shape.
     * Routing the callback through a named method (toSummaryArray() below)
     * whose own @return is array<string, mixed> makes PHPStan use that
     * declared type for the generic inference instead — satisfying both this
     * method's annotation and PaginatedData::fromPaginator()'s expectation.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function handle(User $user): LengthAwarePaginator
    {
        return Scenario::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(self::PER_PAGE)
            ->through($this->toSummaryArray(...));
    }

    /**
     * @return array<string, mixed>
     */
    private function toSummaryArray(Scenario $scenario): array
    {
        return ScenarioSummaryData::fromModel($scenario)->toArray();
    }
}
