import { Link, router } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Search, ShieldCheck, UserPlus, Users, UserX } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';

export default function UsersIndex({ users, roles, role_libelles, filters, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [role, setRole] = useState(filters.role ?? 'all');
    const usersData = users.data ?? [];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', { q, role: role === 'all' ? '' : role, inactifs: filters.inactifs }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Utilisateurs"
            breadcrumbs={[{ label: 'Administration' }, { label: 'Utilisateurs' }]}
            actions={
                <>
                    <PdfExportButton reportKey="liste_users" filtres={filters} />
                    {can.create && (
                        <Button asChild>
                            <Link href="/admin/users/create"><UserPlus className="h-4 w-4" />Nouvel utilisateur</Link>
                        </Button>
                    )}
                </>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="Administration"
                title="Utilisateurs"
                description="Gestion des comptes, rôles, accès et rattachements organisationnels de la plateforme TB-PAPA."
                metrics={[
                    { icon: Users, label: 'Utilisateurs', value: Number(users.total ?? usersData.length).toLocaleString('fr-FR') },
                    { icon: ShieldCheck, label: 'Rôles', value: Number(roles.length ?? 0).toLocaleString('fr-FR') },
                    { icon: UserX, label: 'Inactifs visibles', value: Number(usersData.filter((u: any) => !u.actif).length).toLocaleString('fr-FR') },
                    { icon: KeyRound, label: 'Sécurité', value: 'RBAC' },
                ]}
                footer="Contrôle des habilitations et séparation des responsabilités"
                footerIcon={ShieldCheck}
            />

            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-5">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Nom, email, matricule…" className="pl-9" />
                        </div>
                        <Select value={role} onValueChange={setRole}>
                            <SelectTrigger><SelectValue placeholder="Rôle" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous rôles</SelectItem>
                                {roles.map((r: string) => (
                                    <SelectItem key={r} value={r}>{role_libelles[r] ?? r}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <label className="flex items-center gap-2 rounded-md border px-3 text-sm cursor-pointer hover:bg-accent">
                            <input type="checkbox" checked={!!filters.inactifs} onChange={(e) => router.get('/admin/users', { ...filters, inactifs: e.target.checked ? 1 : 0 }, { preserveState: true })} className="h-4 w-4" />
                            Inactifs
                        </label>
                        <Button type="submit" variant="outline">Filtrer</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Utilisateur</TableHead>
                                <TableHead>Matricule</TableHead>
                                <TableHead>Direction</TableHead>
                                <TableHead>Rôles</TableHead>
                                <TableHead>Dernière connexion</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {usersData.map((u: any) => (
                                <TableRow key={u.id} className={u.actif ? '' : 'opacity-60'}>
                                    <TableCell>
                                        <p className="font-medium">{u.name}</p>
                                        <p className="text-xs text-muted-foreground">{u.email}</p>
                                        {u.fonction && <p className="text-xs text-muted-foreground">{u.fonction}</p>}
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">{u.matricule ?? '—'}</TableCell>
                                    <TableCell className="text-xs">
                                        {u.direction ? `${u.direction.code} — ${u.direction.libelle}` : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {u.roles.map((r: any) => (
                                                <Badge key={r.id} variant="secondary" className="text-[10px]">
                                                    {role_libelles[r.name] ?? r.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-xs">
                                        {u.derniere_connexion_at ? formatDate(u.derniere_connexion_at) : 'Jamais'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Button size="sm" variant="ghost" asChild title="Modifier">
                                                <Link href={`/admin/users/${u.id}/edit`}><Pencil className="h-4 w-4" /></Link>
                                            </Button>
                                            {u.actif && (
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => {
                                                        if (confirm('Désactiver cet utilisateur ?')) {
                                                            router.delete(`/admin/users/${u.id}`);
                                                        }
                                                    }}
                                                    title="Désactiver"
                                                >
                                                    <UserX className="h-4 w-4 text-destructive" />
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}
