import { Link, router } from '@inertiajs/react';
import { AlertTriangle, ClipboardCheck } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function RecommandationsIndex({ recommandations, statuts, priorites, filters }: any) {
    const items = recommandations.data ?? [];

    const setFilter = (key: string, value: string | boolean) => {
        router.get('/audit/recommandations', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout
            pageTitle="Recommandations"
            breadcrumbs={[{ label: 'Audit interne', href: '/audit' }, { label: 'Recommandations' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={items} filename="recommandations_audit" meta={{ total: recommandations.total ?? items.length, filtres: filters }} />
                    <PdfExportButton reportKey="liste_audit_recommandations" filtres={filters} />
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Suivi de mise en œuvre"
                    title="Recommandations d'audit"
                    description="Suivi global des recommandations issues des constats. Priorisation, échéances, taux d'avancement."
                    metrics={[
                        { icon: ClipboardCheck, label: 'Total', value: String(recommandations.total ?? items.length) },
                        { icon: AlertTriangle, label: 'En retard', value: items.filter((r: any) => r.date_echeance && new Date(r.date_echeance) < new Date() && !['verifiee', 'rejetee', 'abandonnee'].includes(r.statut)).length.toString() },
                    ]}
                />

                <Card>
                    <CardContent className="p-4 space-y-4">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters.statut ?? ''} onChange={(e) => setFilter('statut', e.target.value)}>
                                <option value="">Tous statuts</option>
                                {statuts.map((s: string) => <option key={s} value={s}>{s}</option>)}
                            </select>
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters.priorite ?? ''} onChange={(e) => setFilter('priorite', e.target.value)}>
                                <option value="">Toutes priorités</option>
                                {priorites.map((p: string) => <option key={p} value={p}>{p}</option>)}
                            </select>
                            <label className="flex items-center gap-2 text-sm">
                                <input type="checkbox" checked={!!filters.en_retard} onChange={(e) => setFilter('en_retard', e.target.checked ? 1 : '')} />
                                Seulement en retard
                            </label>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Mission</TableHead>
                                    <TableHead>Priorité</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Échéance</TableHead>
                                    <TableHead>Responsable</TableHead>
                                    <TableHead className="text-right">%</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={8} className="py-8 text-center text-muted-foreground">Aucune recommandation.</TableCell></TableRow>
                                ) : items.map((r: any) => (
                                    <TableRow key={r.id}>
                                        <TableCell className="font-mono text-xs">{r.code}</TableCell>
                                        <TableCell><Link href={`/audit/recommandations/${r.id}`} className="font-medium hover:underline">{r.libelle}</Link></TableCell>
                                        <TableCell className="text-xs">{r.constat?.mission?.code}</TableCell>
                                        <TableCell className="text-xs">{r.priorite}</TableCell>
                                        <TableCell className="text-xs">{r.statut}</TableCell>
                                        <TableCell className="text-xs">{r.date_echeance ? new Date(r.date_echeance).toLocaleDateString('fr-FR') : '—'}</TableCell>
                                        <TableCell className="text-xs">{r.responsable?.name ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums">{r.pourcentage_avancement}%</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <Pagination pagination={recommandations} label="recommandations" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
