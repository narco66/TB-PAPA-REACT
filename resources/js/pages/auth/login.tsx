import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { type FormEventHandler } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-ceeac-blue/5 via-background to-ceeac-gold/5 p-4">
            <Head title="Connexion" />

            <div className="w-full max-w-md">
                <div className="mb-6">
                    <Button asChild variant="ghost" size="sm">
                        <Link href="/"><ArrowLeft className="h-4 w-4" />Retour à l'accueil</Link>
                    </Button>
                </div>

                <div className="mb-8 flex flex-col items-center text-center">
                    <div className="mb-4 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-border">
                        <img src="/images/LOGO-CEEAC.jpg" alt="Logo CEEAC" className="h-20 w-20 object-contain" />
                    </div>
                    <h1 className="text-2xl font-bold tracking-tight">TB-PAPA-CEEAC</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Suivi & Évaluation du Plan d'Action Prioritaire Annuel
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        Commission de la Communauté Économique des États de l'Afrique Centrale
                    </p>
                </div>

                <Card className="border-border/60 shadow-xl">
                    <CardHeader>
                        <CardTitle>Authentification</CardTitle>
                        <CardDescription>
                            Connectez-vous avec vos identifiants institutionnels.
                        </CardDescription>
                    </CardHeader>

                    <form onSubmit={submit}>
                        <CardContent className="space-y-4">
                            {status ? (
                                <p className="rounded-md bg-success/10 px-3 py-2 text-sm text-success-foreground">
                                    {status}
                                </p>
                            ) : null}

                            <div className="space-y-2">
                                <Label htmlFor="email">Email institutionnel</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    autoFocus
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    placeholder="prenom.nom@ceeac.org"
                                />
                                {errors.email ? (
                                    <p className="text-xs text-destructive">{errors.email}</p>
                                ) : null}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password">Mot de passe</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                />
                                {errors.password ? (
                                    <p className="text-xs text-destructive">{errors.password}</p>
                                ) : null}
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 rounded border-input"
                                />
                                <Label htmlFor="remember" className="font-normal text-sm">
                                    Maintenir la session active
                                </Label>
                            </div>
                        </CardContent>

                        <CardFooter className="flex-col gap-3">
                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                Se connecter
                            </Button>
                            <p className="text-center text-xs text-muted-foreground">
                                Accès réservé aux agents habilités de la Commission.
                            </p>
                        </CardFooter>
                    </form>
                </Card>

                <p className="mt-6 text-center text-xs text-muted-foreground">
                    © {new Date().getFullYear()} CEEAC — Direction des Systèmes d'Information
                </p>
            </div>
        </div>
    );
}
