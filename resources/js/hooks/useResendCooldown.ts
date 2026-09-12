import axios from 'axios';
import { useEffect, useState } from 'react';

// Fallback only, used when a 429 response carries no parseable
// `Retry-After` header — the authoritative cooldown length otherwise
// always comes from the server, never duplicated here as a business rule.
const DEFAULT_COOLDOWN_SECONDS = 60;

interface UseResendCooldownResult {
    cooldownSeconds: number;
    resending: boolean;
    justSent: boolean;
    resend: () => Promise<void>;
}

/**
 * Posts to `url` via plain axios (not Inertia's router) and drives a
 * client-side cooldown from the response: a raw 429 from a structural
 * `throttle:*` middleware carries no `X-Inertia` header, so Inertia's
 * client would only ever surface it as its generic "invalid response"
 * modal — axios lets the caller read the status (and `Retry-After`) and
 * render its own cooldown UI instead.
 */
export default function useResendCooldown(url: string): UseResendCooldownResult {
    const [cooldownSeconds, setCooldownSeconds] = useState(0);
    const [resending, setResending] = useState(false);
    const [justSent, setJustSent] = useState(false);

    useEffect(() => {
        if (cooldownSeconds <= 0) {
            return;
        }

        const timer = setInterval(() => {
            setCooldownSeconds((seconds) => Math.max(0, seconds - 1));
        }, 1000);

        return () => clearInterval(timer);
    }, [cooldownSeconds]);

    const resend = async () => {
        setResending(true);
        setJustSent(false);

        try {
            await axios.post(url);
            setJustSent(true);
            setCooldownSeconds(DEFAULT_COOLDOWN_SECONDS);
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.status === 429) {
                const retryAfter = Number(
                    error.response.headers['retry-after'],
                );
                setCooldownSeconds(
                    Number.isFinite(retryAfter) && retryAfter > 0
                        ? retryAfter
                        : DEFAULT_COOLDOWN_SECONDS,
                );
            }
        } finally {
            setResending(false);
        }
    };

    return { cooldownSeconds, resending, justSent, resend };
}
