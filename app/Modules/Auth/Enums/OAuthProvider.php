<?php

namespace App\Modules\Auth\Enums;

use App\Modules\Auth\DTOs\OAuthRawClaimsData;

enum OAuthProvider: string
{
    case GOOGLE = 'google';

    public function label(): string
    {
        return match ($this) {
            self::GOOGLE => 'Google',
        };
    }

    /**
     * Whether $claims proves the provider verified the user's email address
     * before handing it to us. Google's userinfo response always carries an
     * explicit boolean `email_verified` claim, so it is required to be true.
     */
    public function hasVerifiedEmail(OAuthRawClaimsData $claims): bool
    {
        return match ($this) {
            self::GOOGLE => $claims->emailVerified,
        };
    }
}
