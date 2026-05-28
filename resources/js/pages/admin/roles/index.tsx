import { Link, router, useForm } from '@inertiajs/react';
import { LoaderCircle, Lock, Pencil, Plus, ShieldCheck, Trash2, Users } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface RoleData {
    id: number;
    name: string;
    libelle: string;
    permissions_count: number;
    users_count: number;
}

export default function RolesIndex({ roles, role_libelles, can }: { roles: RoleData[]; role_libelles: Record<string, string>; can: { manage: boolean } }) {
    const [showForm, setShowForm] = useState(false);
    const systemeRoles = Object.keys(role_libelles);

    const f = useForm({ name: '', libelle: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.post('/admin/roles', { onSuccess: () => { f.reset(); setShowForm(false); } });
    };

    const destroy = (r: RoleData) => {
        if (confirm(`Supprimer le rôle « ${r.name} » ? Cette action est irréversible.`)) {
            router.delete(`/admin/roles/${r.id}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout
            pageTitle="Rôles"
            breadcrumbs={[{ label: 'Administration système' }, { label: 'Rôles' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={roles} filename="roles" meta={{ total: roles.length }} />
                    {can.manage && (
                        <Button onClick={() => setShowForm(!showForm)}>
                            <Plus className="h-4 w-4" />{showForm ? 'Annuler' : 'Nouveau rôle'}
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Sécurité & RBAC"
                    title="Gestion des rôles"
                    description="Rôles institutionnels CEEAC. Chaque rôle agrège un ensemble de permissions appliquées aux utilisateurs rattachés. Conforme RGPD, OWASP, ISO 27001 (principe du moindre privilège)."
                    metrics={[
                        { icon: ShieldCheck, label: 'Rôles', value: String(roles.length) },
                        { icon: Lock, label: 'Système', value: String(systemeRoles.length) },
                        { icon: Users, label: 'Utilisateurs rattachés', value: String(roles.reduce((s, r) => s + r.users_count, 0)) },
                    ]}
                />

                {showForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouveau rôle</CardTitle></CardHeader>
                        <form onSubmit={submit}>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Identifiant technique * <span className="text-xs text-muted-foreground">(snake_case, sans accent)</span></Label>
                                    <Input value={f.data.name} onChange={(e) => f.setData('name', e.target.value)} placeholder="ex: gestionnaire_papa" required pattern="^[a-z_]+$" />
                                    {f.errors.name && <p className="text-xs text-destructive">{f.errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>Libellé humain</Label>
                                    <Input value={f.data.libelle} onChange={(e) => f.setData('libelle', e.target.value)} placeholder="Gestionnaire PAPA" />
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button type="button" variant="outline" onClick={() => setShowForm(false)}>Annuler</Button>
                                <Button type="submit" disabled={f.processing}>
                                    {f.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Créer
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Identifiant</TableHead>
                                    <TableHead>Libellé institutionnel</TableHead>
                                    <TableHead className="text-right">Permissions</TableHead>
                                    <TableHead className="text-right">Utilisateurs</TableHead>
                                    <TableHead>Statut</TableHead>
                                    {can.manage && <TableHead className="text-right">Actions</TableHead>}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.length === 0 ? (
                                    <TableRow><TableCell colSpan={6} className="py-8 text-center text-muted-foreground">Aucun rôle.</TableCell></TableRow>
                                ) : roles.map((r) => {
                                    const isSysteme = systemeRoles.includes(r.name);
                                    return (
                                        <TableRow key={r.id}>
                                            <TableCell className="font-mono text-xs">{r.name}</TableCell>
                                            <TableCell className="font-medium">{r.libelle}</TableCell>
                                            <TableCell className="text-right tabular-nums">{r.permissions_count}</TableCell>
                                            <TableCell className="text-right tabular-nums">{r.users_count}</TableCell>
                                            <TableCell>
                                                {isSysteme ? (
                                                    <span className="inline-flex items-center gap-1 text-xs text-amber-700">
                                                        <Lock className="h-3 w-3" />Système
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">Custom</span>
                                                )}
                                            </TableCell>
                                            {can.manage && (
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button size="sm" variant="ghost" asChild title="Modifier les permissions">
                                                            <Link href={`/admin/roles/${r.id}/edit`}>
                                                                <Pencil className="h-3.5 w-3.5" />
                                                            </Link>
                                                        </Button>
                                                        {!isSysteme && r.users_count === 0 && (
                                                            <Button size="sm" variant="ghost" onClick={() => destroy(r)} title="Supprimer" className="hover:text-destructive">
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <div className="text-xs text-muted-foreground">
                    <Lock className="inline h-3 w-3 mr-1" />
                    Les rôles « Système » sont définis par le seeder institutionnel et ne peuvent pas être supprimés. Leurs permissions restent modifiables.
                </div>
            </div>
        </AppLayout>
    );
}
