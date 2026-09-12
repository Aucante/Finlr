export type Plan = 'free' | 'pro_monthly' | 'pro_yearly' | 'enterprise';

export type Permission = 'export_reports' | 'create_project' | 'advanced_calculator';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    /** Mirrors auth.user.two_factor_enabled (HandleInertiaRequests::share()) — derived boolean only, never the raw timestamp. */
    two_factor_enabled: boolean;
}

export interface Auth {
    user: User | null;
    plan: Plan | null;
    permissions: Permission[];
}

export interface PageProps {
    auth: Auth;
    [key: string]: unknown;
}

/** Mirrors App\Modules\Shared\DTOs\PaginatedData::toArray(). */
export interface Paginated<T> {
    data: T[];
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
}

/**
 * Page props for routes gated by the `auth` middleware, where `auth.user` is
 * guaranteed to be present.
 */
export interface AuthenticatedPageProps extends PageProps {
    auth: Auth & { user: User };
}
