<?php

namespace App\Modules\Auth\DTOs;

use App\Modules\Auth\Models\TwoFactorTrustedDevice;
use Carbon\CarbonInterface;

/**
 * Display-only view of a trusted device for the /settings list. Never
 * carries `selector` nor `hashed_validator`: this shape is serialised into
 * the page's HTML, and the selector is half of a working cookie.
 */
readonly class TrustedDeviceData
{
    public function __construct(
        public int $id,
        public ?string $label,
        public ?CarbonInterface $createdAt,
        public CarbonInterface $expiresAt,
        public bool $isCurrent,
    ) {}

    public static function fromModel(TwoFactorTrustedDevice $device, bool $isCurrent): self
    {
        return new self(
            id: $device->id,
            label: $device->label,
            createdAt: $device->created_at,
            expiresAt: $device->expires_at,
            isCurrent: $isCurrent,
        );
    }

    /**
     * @return array{id: int, label: string|null, createdAt: string|null, expiresAt: string, isCurrent: bool}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'createdAt' => $this->createdAt?->toISOString(),
            'expiresAt' => $this->expiresAt->toISOString(),
            'isCurrent' => $this->isCurrent,
        ];
    }
}
