<?php

namespace App\Modules\Auth\DTOs;

use App\Modules\Auth\Enums\OAuthProvider;
use App\Modules\Auth\Exceptions\OAuthEmailNotVerifiedException;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

readonly class OAuthUserData
{
    public function __construct(
        public string $providerId,
        public string $email,
        public string $name,
    ) {}

    public static function fromSocialiteUser(OAuthProvider $provider, SocialiteUser $socialiteUser): self
    {
        $email = (string) $socialiteUser->getEmail();

        if ($email === '') {
            throw new RuntimeException('The OAuth provider did not return an email address.');
        }

        // The Contracts\User interface (the declared return type of
        // Socialite::driver()->user()) has no getRaw() — only the concrete
        // AbstractUser base, which every real Two.x driver (Google
        // included) extends, exposes the provider's raw claims.
        $rawClaims = $socialiteUser instanceof AbstractUser ? $socialiteUser->getRaw() : [];
        $claims = OAuthRawClaimsData::fromGoogleRawClaims($rawClaims);

        if (! $provider->hasVerifiedEmail($claims)) {
            throw new OAuthEmailNotVerifiedException(
                "The {$provider->value} account's email address is not verified.",
            );
        }

        $name = (string) ($socialiteUser->getName() ?: $socialiteUser->getNickname() ?: Str::before($email, '@'));

        return new self(
            providerId: (string) $socialiteUser->getId(),
            email: $email,
            name: $name,
        );
    }
}
