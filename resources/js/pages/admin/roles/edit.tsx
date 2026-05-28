import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, Save, ShieldCheck } from 'lucide-react';
import { type FormEvent, useMemo, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface PermItem {
    id: number;
    name: string;
    libelle: string;
    assigned: boolean;
}

interface DomaineGroup {
    domaine: string;
    libelle_domaine: string;
    permissions: PermItem[];
}

export default function RoleEdit({ role, permissions_par_domaine }: { role: { id: number; name: string; libelle: string; users_count: number }; permissions_par_domaine: DomaineGroup[] }) {
    const initiallyAssigned = useMemo(
        () => permissions_par_domaine.flatMap((g) => g.permissions.filter((p) => p.assigned).map((p) => p.name)),
        [permissions_par_domaine]
    );

    const f = useForm({ permissions: initiallyAssigned });
    const [filter, setFilter] = useState('');

    const togglePerm = (name: string) => {
        const set = new Set(f.data.permissions);
        if (set.has(name)) set.delete(name);
        else set.add(name);
        f.setData('permissions', [...set]);
    };

    const toggleDomaine = (groupe: DomaineGroup) => {
        const set = new Set(f.data.permissions);
        const allChecked = groupe.permissions.every((p) => set.has(p.name));
        groupe.permissions.forEach((p) => {
            if (allChecked) set.delete(p.name);
            else set.add(p.name);
        });
        f.setData('permissions', [...set]);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.put(`/admin/roles/${role.id}`, { preserveScroll: true });
    };

    const nbCheck = f.data.permissions.length;
    const totalPerms = permissions_par_domaine.reduce((s, g) => s + g.permissions.length, 0);

    return (
        <AppLayout
            pageTitle={`Permissions — ${role.libelle}`}
            breadcrumbs={[
                { label: 'Administration système' },
                { label: 'Rôles', href: '/admin/roles' },
                { label: role.name },
            ]}
            actions={
                <Button onClick={submit} disabled={f.processing}>
                    {f.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                    Enregistrer
                </Button>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow={`Rôle ${role.name}`}
                    title={role.libelle}
                    description={`Attribuez les permissions octroyées à ce rôle. ${role.users_count} utilisateur(s) en héritent automatiquement.`}
                    metrics={[
                        { icon: ShieldCheck, label: 'Sélectionnées', value: `${nbCheck} / ${totalPerms}` },
                    ]}
                />

                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/admin/roles"><ArrowLeft className="h-4 w-4" />Retour</Link>
                    </Button>
                    <input
                        type="search"
                        placeholder="Filtrer une permission…"
                        className="rounded-md border px-3 py-2 text-sm flex-1 max-w-sm"
                        value={filter}
                        onChange={(e) => setFilter(e.target.value.toLowerCase())}
                    />
                    <div className="ml-auto text-sm text-muted-foreground">
                        Modifications non sauvegardées ? Cliquez sur <strong>Enregistrer</strong>.
                    </div>
                </div>

                <form onSubmit={submit}>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {permissions_par_domaine.map((groupe) => {
                            const permsFiltrees = filter
                                ? groupe.permissions.filter((p) => p.name.toLowerCase().includes(filter) || p.libelle.toLowerCase().includes(filter))
                                : groupe.permissions;

                            if (permsFiltrees.length === 0) return null;

                            const checkedCount = groupe.permissions.filter((p) => f.data.permissions.includes(p.name)).length;
                            const allChecked = checkedCount === groupe.permissions.length;
                            const someChecked = checkedCount > 0 && checkedCount < groupe.permissions.length;

                            return (
                                <Card key={groupe.domaine}>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="flex items-center justify-between text-base">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={allChecked}
                                                    ref={(el) => {
                                                        if (el) el.indeterminate = someChecked;
                                                    }}
                                                    onChange={() => toggleDomaine(groupe)}
                                                    className="h-4 w-4"
                                                />
                                                <span>{groupe.libelle_domaine}</span>
                                            </label>
                                            <span className="text-xs font-normal text-muted-foreground tabular-nums">
                                                {checkedCount} / {groupe.permissions.length}
                                            </span>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-1 pt-0">
                                        {permsFiltrees.map((p) => (
                                            <label key={p.id} className="flex items-center gap-3 rounded-md px-2 py-1.5 hover:bg-accent cursor-pointer text-sm">
                                                <input
                                                    type="checkbox"
                                                    checked={f.data.permissions.includes(p.name)}
                                                    onChange={() => togglePerm(p.name)}
                                                    className="h-4 w-4"
                                                />
                                                <span className="flex-1">{p.libelle}</span>
                                                <code className="text-[10px] text-muted-foreground">{p.name}</code>
                                            </label>
                                        ))}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>

                    <div className="flex items-center justify-end gap-2 mt-6">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/admin/roles">Annuler</Link>
                        </Button>
                        <Button type="submit" disabled={f.processing}>
                            {f.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                            Enregistrer ({nbCheck} permission{nbCheck > 1 ? 's' : ''})
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
