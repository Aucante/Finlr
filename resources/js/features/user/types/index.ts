/**
 * Mirror of App\Modules\Auth\DTOs\TrustedDeviceData::toArray(). Never
 * carries the selector nor the validator hash — display fields only.
 */
export interface TrustedDevice {
    id: number;
    // Null for unrecognised User-Agents and for rows issued before the
    // label existed: rendered with a translated generic fallback.
    label: string | null;
    createdAt: string | null;
    expiresAt: string;
    isCurrent: boolean;
}
