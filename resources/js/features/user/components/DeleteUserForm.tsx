import { useForm } from '@inertiajs/react';
import { useRef, useState, type SubmitEvent } from 'react';
import { useTranslation } from 'react-i18next';

import Modal from '@/components/ui/modal';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function DeleteUserForm() {
    const { t } = useTranslation();
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        setConfirmingUserDeletion(true);
    };

    const deleteUser = (e: SubmitEvent) => {
        e.preventDefault();

        destroy(route('settings.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };

    return (
        <Card className="gap-0 border-destructive/30 bg-destructive/5 py-0">
            <CardHeader className="gap-1.5 border-b border-destructive/20 py-5">
                <div className="flex items-baseline gap-2">
                    <span aria-hidden className="font-mono text-xs text-destructive">
                        05
                    </span>
                    <CardTitle className="text-base">
                        {t('settings.dangerZone.title')}
                    </CardTitle>
                </div>
                <CardDescription>
                    {t('settings.dangerZone.description')}
                </CardDescription>
            </CardHeader>

            <CardContent className="py-6">
                <Button variant="destructive" onClick={confirmUserDeletion}>
                    {t('settings.dangerZone.button')}
                </Button>

                <Modal show={confirmingUserDeletion} onClose={closeModal}>
                    <form onSubmit={deleteUser} className="flex flex-col gap-4 p-6">
                        <h2 className="text-lg font-semibold text-foreground">
                            {t('settings.dangerZone.confirmTitle')}
                        </h2>

                        <p className="text-sm text-muted-foreground">
                            {t('settings.dangerZone.confirmDescription')}
                        </p>

                        <div className="flex flex-col gap-2">
                            <Label htmlFor="password" className="sr-only">
                                {t('settings.dangerZone.password')}
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                placeholder={t(
                                    'settings.dangerZone.password',
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
                                {t('settings.dangerZone.cancel')}
                            </Button>

                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                {t('settings.dangerZone.confirm')}
                            </Button>
                        </div>
                    </form>
                </Modal>
            </CardContent>
        </Card>
    );
}
