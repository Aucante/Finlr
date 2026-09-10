import {
    Dialog,
    DialogPanel,
    Transition,
    TransitionChild,
} from '@headlessui/react';
import { useId, type ReactNode } from 'react';

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
    // A hardcoded id here would collide once more than one Modal is
    // mounted on the same page (now true: DeleteUserForm and
    // TwoFactorDisableDialog both render one) — two elements sharing the
    // same `id` confuses Headless UI's own internal bookkeeping for that
    // id (focus restoration / outside-click targeting), which is what
    // made a modal's own close transition get stuck and visually
    // overlap the next one opened. useId() keeps this id stable per
    // component instance and guaranteed unique across the page.
    const id = useId();

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

    return (
        <Transition show={show} leave="duration-200">
            <Dialog
                as="div"
                id={id}
                className="fixed inset-0 z-50 flex transform items-center overflow-y-auto px-4 py-6 sm:px-0"
                onClose={close}
            >
                <TransitionChild
                    enter="ease-out duration-300"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    {/*
                        A modal backdrop must stay a dark scrim in both
                        themes — `bg-foreground/50` was wrong here: the
                        `foreground` token is the theme's *text* color,
                        which flips to near-white in dark mode
                        (`--foreground: oklch(0.985 0 0)` under `.dark`,
                        resources/css/app.css), turning this into a
                        translucent light wash that fails to dim the page
                        and lets background content bleed through the
                        panel instead of sitting behind a proper scrim.
                        `black/50` is deliberately theme-invariant, unlike
                        every other color here.
                    */}
                    <div className="absolute inset-0 bg-black/50 transition-opacity" />
                </TransitionChild>

                <TransitionChild
                    enter="ease-out duration-300"
                    enterFrom="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enterTo="opacity-100 translate-y-0 sm:scale-100"
                    leave="ease-in duration-200"
                    leaveFrom="opacity-100 translate-y-0 sm:scale-100"
                    leaveTo="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <DialogPanel
                        className={`mb-6 transform overflow-hidden rounded-lg border border-border bg-card text-card-foreground shadow-xl transition-[opacity,transform] sm:mx-auto sm:w-full ${maxWidthClass}`}
                    >
                        {children}
                    </DialogPanel>
                </TransitionChild>
            </Dialog>
        </Transition>
    );
}
