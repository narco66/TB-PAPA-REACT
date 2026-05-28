import { router } from '@inertiajs/react';
import { Check, KeyRound, Lock } from 'lucide-react';
import { useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

interface PermissionRow {
    id: number;
    name: string;
    roles: Record<string, boolean>;
    roles_count: number;
}

export default function PermissionsIndex({ permissions, roles, role_libelles }: {
    permissions: PermissionRow[];
    roles: Array<{ id: number; name: string }>;
    role_libelles: Record<string, string>;
}) {
    const [filter, setFilter] = useState('');

    const filtered = filter
        ? permissions.filter((p) => p.name.toLowerCase().includes(filter.toLowerCase()))
        : permissions;

    return (
        <AppLayout
            pageTitle="Permissions"
            breadcrumbs={[{ label: 'Administration système' }, { label: 'Permissions' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={permissions} filename="permissions" meta={{ total: permissions.length, roles: roles.length }} />
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Matrice de couverture"
                    title="Permissions × Rôles"
                    description="Vue consolidée de toutes les permissions de l'application et de leur attribution par rôle institutionnel. Pour modifier les permissions d'un rôle, allez dans Rôles → Modifier."
                    metrics={[
                        { icon: KeyRound, label: 'Permissions totales', value: String(permissions.length) },
                        { icon: Lock, label: 'Rôles', value: String(roles.length) },
                    ]}
                />

                <Card>
                    <CardContent className="p-4">
                        <Input
                            type="search"
                            placeholder="Filtrer une permission…"
                            value={filter}
                            onChange={(e) => setFilter(e.target.value)}
                            className="max-w-sm"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0 overflow-x-auto">
                        <table className="w-full text-xs">
                            <thead className="border-b bg-muted/50 sticky top-0">
                                <tr>
                                    <th className="text-left px-3 py-2 font-semibold sticky left-0 bg-muted/50 min-w-[280px]">Permission</th>
                                    <th className="text-center px-2 py-2 font-semibold tabular-nums">Couv.</th>
                                    {roles.map((r) => (
                                        <th key={r.id} className="text-center px-2 py-2 font-semibold text-[10px] uppercase tracking-wider whitespace-nowrap" title={role_libelles[r.name] ?? r.name}>
                                            {r.name}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {filtered.length === 0 ? (
                                    <tr><td colSpan={roles.length + 2} className="py-8 text-center text-muted-foreground">Aucune permission ne correspond.</td></tr>
                                ) : filtered.map((p) => (
                                    <tr key={p.id} className="border-b hover:bg-accent/40 transition-colors">
                                        <td className="px-3 py-1.5 font-mono sticky left-0 bg-card">{p.name}</td>
                                        <td className="text-center tabular-nums px-2">
                                            <span className={`inline-block px-1.5 rounded ${p.roles_count === 0 ? 'bg-red-100 text-red-700' : p.roles_count <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}`}>
                                                {p.roles_count}
                                            </span>
                                        </td>
                                        {roles.map((r) => (
                                            <td key={r.id} className="text-center px-2 py-1.5">
                                                {p.roles[r.name] ? (
                                                    <Check className="inline h-3.5 w-3.5 text-emerald-600" />
                                                ) : (
                                                    <span className="text-muted-foreground/30">—</span>
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <div className="text-xs text-muted-foreground">
                    Pour <strong>modifier les permissions d'un rôle</strong>, allez dans <a href="/admin/roles" className="text-ceeac-blue hover:underline">Administration → Rôles</a> et cliquez sur l'icône d'édition d'un rôle.
                </div>
            </div>
        </AppLayout>
    );
}
