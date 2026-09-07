<?php

namespace App\Modules\Subscriptions\Enums;

enum Plan: string
{
    case FREE = 'free';
    case PRO_MONTHLY = 'pro_monthly';
    case PRO_YEARLY = 'pro_yearly';
    case ENTERPRISE = 'enterprise';

    public function isPaid(): bool
    {
        return $this !== self::FREE;
    }

    public function grants(Permission $permission): bool
    {
        return match ($this) {
            self::FREE => $permission === Permission::CREATE_PROJECT,
            self::PRO_MONTHLY, self::PRO_YEARLY, self::ENTERPRISE => true,
        };
    }

    public function maxProjectsAllowed(): int
    {
        return match ($this) {
            self::FREE => 1,
            self::PRO_MONTHLY, self::PRO_YEARLY => 10,
            self::ENTERPRISE => PHP_INT_MAX,
        };
    }
}
