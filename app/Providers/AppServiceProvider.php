<?php

namespace App\Providers;

use App\Modules\Subscriptions\Enums\Permission;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->registerSubscriptionGates();
    }

    private function registerSubscriptionGates(): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, function (User $user) use ($permission): bool {
                return $user->subscription_plan->grants($permission);
            });
        }
    }
}
