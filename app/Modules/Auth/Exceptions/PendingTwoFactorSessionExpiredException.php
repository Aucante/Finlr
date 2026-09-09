<?php

namespace App\Modules\Auth\Exceptions;

use RuntimeException;

/**
 * Thrown when a two-factor challenge route is hit without a valid pending
 * login state in session — either none was ever created, or it expired
 * (CONCEPTION.md, section 6: closing the tab mid-challenge, or letting the
 * validity window run out). Rendered globally in bootstrap/app.php, the
 * same way SimulationEngineUnavailableException already is.
 */
class PendingTwoFactorSessionExpiredException extends RuntimeException {}
