<?php

namespace Tests\Unit\Auth;

use App\Modules\Auth\Actions\GenerateAndSendTwoFactorCodeAction;
use App\Modules\Auth\Models\TwoFactorCode;
use App\Modules\Auth\Notifications\TwoFactorCodeNotification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GenerateAndSendTwoFactorCodeActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_a_six_digit_zero_padded_code_never_stored_in_clear(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        (new GenerateAndSendTwoFactorCodeAction)->handle($user);

        $row = TwoFactorCode::query()->where('user_id', $user->id)->firstOrFail();

        Notification::assertSentTo(
            $user,
            TwoFactorCodeNotification::class,
            function (TwoFactorCodeNotification $notification) use ($row): bool {
                $this->assertMatchesRegularExpression('/^\d{6}$/', $notification->code);
                $this->assertTrue(Hash::check($notification->code, $row->code_hash));
                $this->assertNotSame($notification->code, $row->code_hash);

                return true;
            },
        );
    }

    public function test_a_new_call_replaces_the_previous_pending_code(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        (new GenerateAndSendTwoFactorCodeAction)->handle($user);
        $firstHash = TwoFactorCode::query()->where('user_id', $user->id)->value('code_hash');

        (new GenerateAndSendTwoFactorCodeAction)->handle($user);

        $this->assertSame(1, TwoFactorCode::query()->where('user_id', $user->id)->count());
        $this->assertNotSame($firstHash, TwoFactorCode::query()->where('user_id', $user->id)->value('code_hash'));
    }
}
