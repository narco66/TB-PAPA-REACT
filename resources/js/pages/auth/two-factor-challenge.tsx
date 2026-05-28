import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, ShieldCheck } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery_code: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/two-factor-challenge', {
            onFinish: () => reset('code', 'recovery_code'),
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-linear-to-br from-ceeac-blue/5 via-background to-ceeac-gold/5 p-4">
            <Head title="Double authentification" />

            <div className="w-full max-w-md">
                <div className="mb-8 flex flex-col items-center text-center">
                    <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-ceeac-blue shadow-lg">
                        <ShieldCheck className="h-8 w-8 text-white" />
                    </div>
                    <h1 className="text-2xl font-bold tracking-tight">Double authentification</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Saisissez le code à 6 chiffres généré par votre application d'authentification.
                    </p>
                </div>

                <Card className="border-border/60 shadow-xl">
                    <CardHeader>
                        <CardTitle>Vérification 2FA</CardTitle>
                        <CardDescription>
                            {useRecovery
                                ? "Utilisez l'un de vos codes de récupération à usage unique."
                                : 'Code TOTP — Google Authenticator, Microsoft Authenticator, Authy…'}
                        </CardDescription>
                    </CardHeader>

                    <form onSubmit={submit}>
                        <CardContent className="space-y-4">
                            {useRecovery ? (
                                <div className="space-y-2">
                                    <Label htmlFor="recovery_code">Code de récupération</Label>
                                    <Input
                                        id="recovery_code"
                                        autoComplete="one-time-code"
                                        autoFocus
                                        value={data.recovery_code}
                                        onChange={(e) => setData('recovery_code', e.target.value)}
                                        placeholder="xxxxx-xxxxx"
                                        className="font-mono tracking-wider"
                                        required
                                    />
                                    {errors.recovery_code ? <p className="text-xs text-destructive">{errors.recovery_code}</p> : null}
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    <Label htmlFor="code">Code à 6 chiffres</Label>
                                    <Input
                                        id="code"
                                        type="text"
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        autoFocus
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                                        placeholder="123456"
                                        maxLength={6}
                                        className="text-center text-lg tracking-[0.5em] font-mono"
                                        required
                                    />
                                    {errors.code ? <p className="text-xs text-destructive">{errors.code}</p> : null}
                                </div>
                            )}
                        </CardContent>

                        <CardFooter className="flex-col gap-3">
                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                Vérifier
                            </Button>
                            <button
                                type="button"
                                onClick={() => {
                                    setUseRecovery(!useRecovery);
                                    reset('code', 'recovery_code');
                                }}
                                className="text-xs text-muted-foreground hover:text-primary hover:underline"
                            >
                                {useRecovery ? '← Utiliser un code TOTP' : 'Utiliser un code de récupération'}
                            </button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </div>
    );
}
