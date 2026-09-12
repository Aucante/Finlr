<?php

namespace Tests\Feature\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdHocTwoFactorDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_a_never_activated_account_sends_no_two_factor_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'diagnostic-null-2fa@finlr.test',
        ]);

        $this->assertNull($user->two_factor_enabled_at);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Notification::assertNothingSent();
    }
}
