import { Dialog, DialogPanel } from '@headlessui/react';
import type { ReactNode } from 'react';

interface ModalProps {
    children: ReactNode;
    show?: boolean;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
    closeable?: boolean;
    onClose?: () => void;
}

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
}: ModalProps) {
    const close = () => {
        if (closeable) {
            onClose();
        }
    };

    const maxWidthClass = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[maxWidth];

    // No fade/scale animation here on purpose, after this being the
    // actual root cause of a real, repeatedly-confirmed bug: wrapping
    // Dialog in an animated <Transition>/<TransitionChild> (or using
    // Dialog's own `transition` prop) left the panel permanently stuck
    // at its `opacity-0`/`translate-y-4` *entering* styles in this
    // environment — verified live, in a real browser, on a fresh tab:
    // `getComputedStyle` kept reporting the panel's opacity as 0 no
    // matter how long the wait, and its className never advanced past
    // the enter-from classes to the enter-to ones. A backdrop and panel
    // stuck at opacity 0 while still `fixed inset-0`/still mounted is
    // exactly what let a click meant for the password field land on
    // whatever was actually in front of it and close the dialog instead
    // of focusing the field. Dialog's own `open`/`onClose` already fully
    // control mount/unmount and focus-trap/outside-click/Escape
    // behaviour without any animation library involved, so this renders
    // instantly opaque and correct every time instead of gambling on a
    // transition library ever finishing.
    return (
        <Dialog
            open={show}
            onClose={close}
            className="fixed inset-0 z-50 flex items-center overflow-y-auto px-4 py-6 sm:px-0"
        >
            <div aria-hidden="true" className="fixed inset-0 bg-black/50" />

            <DialogPanel
                className={`relative mb-6 w-full overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow-xl sm:mx-auto ${maxWidthClass}`}
            >
                {children}
            </DialogPanel>
        </Dialog>
    );
}
