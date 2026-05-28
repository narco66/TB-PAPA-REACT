import { router, useForm } from '@inertiajs/react';
import { Check, KeyRound, LoaderCircle, RotateCcw, ShieldCheck, ShieldOff } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    enabled: boolean;
    pending: boolean;
    qrSvg: string | null;
    secret: string | null;
    recoveryCodes: string[];
}

export default function TwoFactorSettings({ enabled, pending, qrSvg, secret, recoveryCodes }: Props) {
    const confirmForm = useForm({ code: '' });
    const disableForm = useForm({ password: '' });
    const recoveryForm = useForm({ password: '' });
    const [showRecovery, setShowRecovery] = useState(false);

    const enable = () => router.post('/settings/two-factor/enable', {}, { preserveScroll: true });

    const confirm: FormEventHandler = (e) => {
        e.preventDefault();
        confirmForm.post('/settings/two-factor/confirm', { preserveScroll: true });
    };

    const disable: FormEventHandler = (e) => {
        e.preventDefault();
        disableForm.delete('/settings/two-factor', { preserveScroll: true });
    };

    const regenerate: FormEventHandler = (e) => {
        e.preventDefault();
        recoveryForm.post('/settings/two-factor/recovery-codes', { preserveScroll: true });
    };

    return (
        <AppLayout
            pageTitle="Double authentification (2FA)"
            breadcrumbs={[{ label: 'Réglages' }, { label: 'Double authentification' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Sécurité du compte"
                    description="Activation et gestion de l'authentification à deux facteurs (TOTP)."
                />

                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle className="flex items-center gap-2">
                                    <ShieldCheck className="h-5 w-5" />
                                    État de la double authentification
                                </CardTitle>
                                <CardDescription className="mt-1">
                                    Ajoute une couche de sécurité supplémentaire à votre compte institutionnel.
                                </CardDescription>
                            </div>
                            {enabled ? (
                                <Badge variant="success" className="gap-1">
                                    <Check className="h-3 w-3" />
                                    Activée
                                </Badge>
                            ) : pending ? (
                                <Badge variant="warning">En cours de configuration</Badge>
                            ) : (
                                <Badge variant="secondary">Désactivée</Badge>
                            )}
                        </div>
                    </CardHeader>

                    {!enabled && !pending && (
                        <CardContent className="border-t pt-4">
                            <p className="text-sm text-muted-foreground">
                                Téléchargez une application compatible TOTP (Google Authenticator, Microsoft Authenticator, Authy) puis
                                cliquez ci-dessous pour générer votre secret.
                            </p>
                            <Button onClick={enable} className="mt-4">
                                <ShieldCheck className="h-4 w-4" />
                                Activer la 2FA
                            </Button>
                        </CardContent>
                    )}

                    {pending && qrSvg && (
                        <CardContent className="border-t pt-4 space-y-4">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <p className="text-sm font-medium mb-2">1. Scannez le QR code</p>
                                    <div
                                        className="rounded-lg border bg-white p-3 inline-block"
                                        dangerouslySetInnerHTML={{ __html: qrSvg }}
                                    />
                                </div>
                                <div>
                                    <p className="text-sm font-medium mb-2">2. Ou saisissez la clé manuellement</p>
                                    <div className="rounded-lg border bg-muted/40 p-3 font-mono text-sm break-all">{secret}</div>
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Conservez cette clé hors-ligne en lieu sûr. Elle ne sera plus jamais affichée.
                                    </p>
                                </div>
                            </div>

                            <form onSubmit={confirm} className="border-t pt-4 space-y-3">
                                <p className="text-sm font-medium">3. Confirmez avec un code généré par votre application</p>
                                <div className="flex items-end gap-2">
                                    <div className="flex-1 space-y-1">
                                        <Label htmlFor="code">Code à 6 chiffres</Label>
                                        <Input
                                            id="code"
                                            inputMode="numeric"
                                            maxLength={6}
                                            value={confirmForm.data.code}
                                            onChange={(e) => confirmForm.setData('code', e.target.value.replace(/\D/g, ''))}
                                            placeholder="123456"
                                            className="font-mono tracking-widest"
                                            autoComplete="one-time-code"
                                        />
                                        {confirmForm.errors.code && (
                                            <p className="text-xs text-destructive">{confirmForm.errors.code}</p>
                                        )}
                                    </div>
                                    <Button type="submit" disabled={confirmForm.processing}>
                                        {confirmForm.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                        Confirmer
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    )}

                    {enabled && (
                        <CardContent className="border-t pt-4">
                            <p className="text-sm text-muted-foreground">
                                La 2FA est active. À chaque connexion, vous devrez saisir un code à 6 chiffres généré par votre
                                application d'authentification.
                            </p>
                        </CardContent>
                    )}
                </Card>

                {enabled && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <KeyRound className="h-5 w-5" />
                                Codes de récupération
                            </CardTitle>
                            <CardDescription>
                                Utilisables une seule fois si vous perdez l'accès à votre application TOTP.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="border-t pt-4 space-y-4">
                            {showRecovery ? (
                                <div className="grid grid-cols-2 gap-2 rounded-md border bg-muted/40 p-3 font-mono text-sm md:grid-cols-4">
                                    {recoveryCodes.map((c) => (
                                        <span key={c}>{c}</span>
                                    ))}
                                </div>
                            ) : (
                                <Button variant="outline" onClick={() => setShowRecovery(true)}>
                                    Afficher les codes
                                </Button>
                            )}

                            <form onSubmit={regenerate} className="flex items-end gap-2 border-t pt-4">
                                <div className="flex-1 space-y-1">
                                    <Label htmlFor="regen_password">Mot de passe actuel</Label>
                                    <Input
                                        id="regen_password"
                                        type="password"
                                        value={recoveryForm.data.password}
                                        onChange={(e) => recoveryForm.setData('password', e.target.value)}
                                        autoComplete="current-password"
                                    />
                                    {recoveryForm.errors.password && (
                                        <p className="text-xs text-destructive">{recoveryForm.errors.password}</p>
                                    )}
                                </div>
                                <Button type="submit" variant="outline" disabled={recoveryForm.processing}>
                                    <RotateCcw className="h-4 w-4" />
                                    Régénérer
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {enabled && (
                    <Card className="border-destructive/30">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-destructive">
                                <ShieldOff className="h-5 w-5" />
                                Désactiver la 2FA
                            </CardTitle>
                            <CardDescription>
                                Réduit le niveau de sécurité de votre compte. À éviter sauf perte d'appareil sans codes de récupération.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="border-t pt-4">
                            <form onSubmit={disable} className="flex items-end gap-2">
                                <div className="flex-1 space-y-1">
                                    <Label htmlFor="disable_password">Mot de passe actuel</Label>
                                    <Input
                                        id="disable_password"
                                        type="password"
                                        value={disableForm.data.password}
                                        onChange={(e) => disableForm.setData('password', e.target.value)}
                                        autoComplete="current-password"
                                    />
                                    {disableForm.errors.password && (
                                        <p className="text-xs text-destructive">{disableForm.errors.password}</p>
                                    )}
                                </div>
                                <Button type="submit" variant="destructive" disabled={disableForm.processing}>
                                    {disableForm.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Désactiver
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
