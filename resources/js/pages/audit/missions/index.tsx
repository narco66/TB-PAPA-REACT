import { Link, router } from '@inertiajs/react';
import { Plus, Target } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function MissionsIndex({ missions, plans, types, statuts, filters, can }: any) {
    const items = missions.data ?? [];

    const setFilter = (key: string, value: string) => {
        router.get('/audit/missions', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout
            pageTitle="Missions d'audit"
            breadcrumbs={[{ label: 'Audit interne', href: '/audit' }, { label: 'Missions' }]}
            actions={
                <>
                    <PdfExportButton reportKey="liste_audit_missions" filtres={filters} />
                    {can.create && (
                        <Button asChild><Link href="/audit/missions/create"><Plus className="h-4 w-4" />Nouvelle mission</Link></Button>
                    )}
                </>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Réalisation"
                    title="Missions d'audit"
                    description="Lettre de mission, périmètre, équipe, calendrier. Cycle terrain → projet de rapport → rapport définitif → clôture."
                    metrics={[{ icon: Target, label: 'Missions', value: String(missions.total ?? items.length) }]}
                />

                <Card>
                    <CardContent className="p-4 space-y-4">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters.plan_id ?? ''} onChange={(e) => setFilter('plan_id', e.target.value)}>
                                <option value="">Tous les plans</option>
                                {plans.map((p: any) => <option key={p.id} value={p.id}>{p.annee} — {p.libelle}</option>)}
                            </select>
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters.type ?? ''} onChange={(e) => setFilter('type', e.target.value)}>
                                <option value="">Tous types</option>
                                {types.map((t: string) => <option key={t} value={t}>{t}</option>)}
                            </select>
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters.statut ?? ''} onChange={(e) => setFilter('statut', e.target.value)}>
                                <option value="">Tous statuts</option>
                                {statuts.map((s: string) => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Titre</TableHead>
                                    <TableHead>Plan</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Priorité</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="text-right">Constats</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground">Aucune mission.</TableCell></TableRow>
                                ) : items.map((m: any) => (
                                    <TableRow key={m.id}>
                                        <TableCell className="font-mono text-xs">{m.code}</TableCell>
                                        <TableCell><Link href={`/audit/missions/${m.id}`} className="font-medium hover:underline">{m.titre}</Link></TableCell>
                                        <TableCell className="text-xs">{m.plan?.annee}</TableCell>
                                        <TableCell className="text-xs">{m.type}</TableCell>
                                        <TableCell className="text-xs">{m.priorite}</TableCell>
                                        <TableCell className="text-xs">{m.statut}</TableCell>
                                        <TableCell className="text-right tabular-nums">{m.constats_count}</TableCell>
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
