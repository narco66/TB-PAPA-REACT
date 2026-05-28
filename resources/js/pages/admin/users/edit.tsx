import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function UserEdit({ user, roles, role_libelles, directions }: any) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name ?? '',
        email: user.email ?? '',
        matricule: user.matricule ?? '',
        fonction: user.fonction ?? '',
        telephone: user.telephone ?? '',
        direction_id: user.direction_id ?? '',
        actif: !!user.actif,
        roles: (user.roles ?? []) as string[],
        password: '',
        password_confirmation: '',
    });

    const toggleRole = (role: string) => {
        setData('roles', data.roles.includes(role) ? data.roles.filter((r) => r !== role) : [...data.roles, role]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    return (
        <AppLayout
            pageTitle={`Modifier ${user.name}`}
            breadcrumbs={[{ label: 'Administration' }, { label: 'Utilisateurs', href: '/admin/users' }, { label: 'Édition' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Modifier un utilisateur"
                    description="Mise à jour des habilitations, profils et paramètres de compte."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/admin/users"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader><CardTitle>Modifier l'utilisateur</CardTitle></CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Nom complet</Label>
                                <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label>Email</Label>
                                <Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                                {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Matricule</Label>
                                <Input value={data.matricule} onChange={(e) => setData('matricule', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Fonction</Label>
                                <Input value={data.fonction} onChange={(e) => setData('fonction', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Téléphone</Label>
                                <Input value={data.telephone} onChange={(e) => setData('telephone', e.target.value)} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Direction</Label>
                            <Select value={String(data.direction_id)} onValueChange={(v) => setData('direction_id', Number(v))}>
                                <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    {directions.map((d: any) => (
                                        <SelectItem key={d.id} value={String(d.id)}>{d.code} — {d.libelle}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <label className="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" checked={data.actif} onChange={(e) => setData('actif', e.target.checked)} className="h-4 w-4" />
                            Compte actif
                        </label>

                        <div className="space-y-2">
                            <Label>Rôles</Label>
                            <div className="grid grid-cols-2 gap-2 rounded-md border p-3 sm:grid-cols-3">
                                {roles.map((r: any) => (
                                    <label key={r.id} className="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" checked={data.roles.includes(r.name)} onChange={() => toggleRole(r.name)} className="h-4 w-4" />
                                        <span>{role_libelles[r.name] ?? r.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-md border bg-muted/30 p-3">
                            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
                                Réinitialiser le mot de passe (optionnel)
                            </p>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <Input type="password" placeholder="Nouveau mot de passe" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                                <Input type="password" placeholder="Confirmation" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                            </div>
                            {errors.password && <p className="mt-1 text-xs text-destructive">{errors.password}</p>}
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/admin/users">Annuler</Link></Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            Enregistrer
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
