<?php

namespace App\Modules\Shared\DTOs;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Generic Inertia-facing envelope around a Laravel paginator — purely
 * transversal (CLAUDE.md's rule for /Shared): it carries whatever the
 * caller already serialised (e.g. a *Data DTO's own toArray()), never
 * inspects or transforms the paginated items themselves.
 */
readonly class PaginatedData
{
    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    public function __construct(
        public array $data,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}

    /**
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $paginator
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            data: $paginator->items(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
        );
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, currentPage: int, lastPage: int, perPage: int, total: int}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'currentPage' => $this->currentPage,
            'lastPage' => $this->lastPage,
            'perPage' => $this->perPage,
            'total' => $this->total,
        ];
    }
}
