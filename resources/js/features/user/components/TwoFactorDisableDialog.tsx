import { useForm } from '@inertiajs/react';
import { useRef, useState, type SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Modal from '@/components/ui/modal';

/** Same patron as DeleteUserForm: current-password re-confirmation in a modal. */
export default function TwoFactorDisableDialog() {
    const { t } = useTranslation();
    const [confirming, setConfirming] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmDisable = () => {
        setConfirming(true);
    };

    const closeModal = () => {
        setConfirming(false);

        clearErrors();
        reset();
    };

    const disable = (e: SubmitEvent) => {
        e.preventDefault();

        destroy(route('two-factor.disable'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    return (
        <>
            {/*
                Destructive, matching DeleteUserForm's own trigger button:
                both reduce the account's security posture, so the trigger
                carries the same visual weight as the modal's own confirm
                action, not a neutral `outline`.
            */}
            <Button
                type="button"
                variant="destructive"
                className="self-start border-destructive/30"
                onClick={confirmDisable}
            >
                {t('settings.security.twoFactor.disable.button')}
            </Button>

            <Modal show={confirming} onClose={closeModal}>
                <form
                    onSubmit={disable}
                    className="flex flex-col gap-4 p-6"
                >
                    <h2 className="text-lg font-semibold text-foreground">
                        {t('settings.security.twoFactor.disable.modalTitle')}
                    </h2>

                    <p className="text-sm text-muted-foreground">
                        {t(
                            'settings.security.twoFactor.disable.modalDescription',
                        )}
                    </p>

                    <div className="flex flex-col gap-2">
                        <Label
                            htmlFor="two_factor_disable_password"
                            className="sr-only"
                        >
                            {t('settings.security.twoFactor.disable.password')}
                        </Label>
                        <Input
                            id="two_factor_disable_password"
                            type="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            placeholder={t(
                                'settings.security.twoFactor.disable.password',
                            )}
                            autoFocus
                            aria-invalid={Boolean(errors.password)}
                        />
                        {errors.password && (
                            <p className="text-xs text-destructive">
                                {errors.password}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={closeModal}
                        >
                            {t('settings.security.twoFactor.disable.cancel')}
                        </Button>

                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                        >
                            {t('settings.security.twoFactor.disable.confirm')}
                        </Button>
                    </div>
                </form>
            </Modal>
        </>
    );
}
