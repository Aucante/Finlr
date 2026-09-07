<?php

namespace App\Modules\Auth\DTOs;

readonly class OAuthRawClaimsData
{
    public function __construct(
        public bool $emailVerified,
    ) {}

    /**
     * The only place allowed to touch a provider's raw claims array — every
     * other consumer (OAuthProvider::hasVerifiedEmail() included) only ever
     * sees this typed DTO. Named after Google specifically (rather than a
     * generic `fromRawClaims`) because Google is the only OAuth provider
     * this app supports — keep it that way if a second provider is ever
     * reintroduced, since claim shapes differ per provider and a generic
     * name would silently invite reusing Google's parsing for a payload it
     * was never written for.
     *
     * @param  array<string, mixed>  $raw
     */
    public static function fromGoogleRawClaims(array $raw): self
    {
        return new self(
            emailVerified: filter_var($raw['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }
}
