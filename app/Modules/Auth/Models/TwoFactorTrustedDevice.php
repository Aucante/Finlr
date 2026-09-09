<?php

namespace App\Modules\Auth\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'selector', 'hashed_validator', 'expires_at'])]
#[Hidden(['hashed_validator'])]
class TwoFactorTrustedDevice extends Model
{
    public const TRUST_DAYS = 30;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Single point of truth for the trust window, so the cookie's own
     * lifetime (set where the cookie is issued) and this row's `expires_at`
     * can never drift apart — the exact pitfall already hit once on
     * `RememberMeServiceProvider` (RAPPORT.md, section 4).
     */
    public static function newExpiry(): Carbon
    {
        return now()->addDays(self::TRUST_DAYS);
    }
}
