import React from 'react';
import { vi } from 'vitest';

export function Head({ children }) {
    return children ?? null;
}

export function Link({ href, children, ...props }) {
    return (
        <a href={href} {...props}>
            {children}
        </a>
    );
}

export function useForm(initialValues) {
    const [data, setDataState] = React.useState(initialValues);
    const [processing] = React.useState(false);
    const [errors] = React.useState({});

    const setData = (key, value) => {
        if (typeof key === 'object' && key !== null) {
            setDataState((prev) => ({ ...prev, ...key }));
        } else {
            setDataState((prev) => ({ ...prev, [key]: value }));
        }
    };

    const post = vi.fn((_url, options) => {
        options?.onFinish?.();
    });
    const patch = vi.fn((_url, options) => {
        options?.onSuccess?.();
        options?.onFinish?.();
    });
    const put = vi.fn((_url, options) => {
        options?.onSuccess?.();
        options?.onFinish?.();
    });
    const destroy = vi.fn((_url, options) => {
        options?.onSuccess?.();
        options?.onFinish?.();
    });

    return {
        data,
        setData,
        post,
        patch,
        put,
        delete: destroy,
        processing,
        errors,
        reset: vi.fn(),
        clearErrors: vi.fn(),
        recentlySuccessful: false,
    };
}

export function usePage() {
    return { props: {} };
}

export const router = {
    get: vi.fn(),
};
