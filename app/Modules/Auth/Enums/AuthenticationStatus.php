<?php

namespace App\Modules\Auth\Enums;

enum AuthenticationStatus
{
    case FullyAuthenticated;
    case PendingTwoFactor;
}
