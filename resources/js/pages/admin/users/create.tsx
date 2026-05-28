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

interface Props {
    roles: Array<{ id: number; name: string }>;
    role_libelles: Record<string, string>;
    directions: Array<{ id: number; code: string; libelle: string }>;
}

export default function UserCreate({ roles, role_libelles, directions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        matricule: '',
        fonction: '',
        telephone: '',
        direction_id: '' as number | '',
        password: '',
        password_confirmation: '',
        roles: [] as string[],
    });

    const toggleRole = (role: string) => {
        setData('roles', data.roles.includes(role) ? data.roles.filter((r) => r !== role) : [...data.roles, role]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/admin/users');
    };

    return (
        <AppLayout
            pageTitle="Nouvel utilisateur"
            breadcrumbs={[{ label: 'Administration' }, { label: 'Utilisateurs', href: '/admin/users' }, { label: 'Création' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Créer un utilisateur"
                    description="Gestion des comptes, rôles et rattachements organisationnels."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/admin/users"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader><CardTitle>Création d'un compte utilisateur</CardTitle></CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nom complet *</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email">Email institutionnel *</Label>
                                <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                                {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="matricule">Matricule</Label>
                                <Input id="matricule" value={data.matricule} onChange={(e) => setData('matricule', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="fonction">Fonction</Label>
                                <Input id="fonction" value={data.fonction} onChange={(e) => setData('fonction', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="telephone">Téléphone</Label>
                                <Input id="telephone" value={data.telephone} onChange={(e) => setData('telephone', e.target.value)} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Direction de rattachement</Label>
                            <Select value={String(data.direction_id)} onValueChange={(v) => setData('direction_id', Number(v))}>
                                <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    {directions.map((d) => (
                                        <SelectItem key={d.id} value={String(d.id)}>{d.code} — {d.libelle}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="password">Mot de passe initial *</Label>
                                <Input id="password" type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                                {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">Confirmation *</Label>
                                <Input id="password_confirmation" type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} required />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Rôles institutionnels</Label>
                            <div className="grid grid-cols-2 gap-2 rounded-md border p-3 sm:grid-cols-3">
                                {roles.map((r) => (
                                    <label key={r.id} className="flex items-center gap-2 text-sm cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.roles.includes(r.name)}
                                            onChange={() => toggleRole(r.name)}
                                            className="h-4 w-4"
                                        />
                                        <span>{role_libelles[r.name] ?? r.name}</span>
                                    </label>
                                ))}
                            </div>
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/admin/users">Annuler</Link></Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            Créer l'utilisateur
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
