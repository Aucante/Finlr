<?php

namespace App\Modules\Auth\Notifications;

use App\Modules\Auth\Models\TwoFactorCode;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Deliberately not `ShouldQueue` (RAPPORT.md, section 2, point 2 de la
 * relecture) : this project has no queue worker running in any
 * environment (dev under Sail, CI), so queuing this notification would
 * make the 2FA code never arrive rather than merely arrive late.
 */
class TwoFactorCodeNotification extends Notification
{
    public function __construct(public readonly string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('auth.two_factor.mail.subject'))
            ->greeting(trans('auth.two_factor.mail.greeting'))
            ->line(trans('auth.two_factor.mail.intro'))
            ->line(trans('auth.two_factor.mail.code_line', ['code' => $this->code]))
            ->line(trans('auth.two_factor.mail.expiry_notice', ['minutes' => TwoFactorCode::VALIDITY_MINUTES]));
    }
}
