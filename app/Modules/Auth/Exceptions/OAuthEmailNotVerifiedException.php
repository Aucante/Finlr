<?php

namespace App\Modules\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown when an OAuth provider does not assert that the authenticating
 * user's email address has been verified (e.g. Google's `email_verified`
 * claim is explicitly false or missing). Trusting an unverified email would
 * let anyone claim ownership of an address they don't control. The message
 * is technical, for logs only — HandleOAuthCallbackController renders a
 * generic, translated error to the end user instead.
 */
class OAuthEmailNotVerifiedException extends RuntimeException {}
