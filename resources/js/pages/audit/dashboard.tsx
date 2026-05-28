import { Link } from '@inertiajs/react';
import { AlertTriangle, ClipboardCheck, FileWarning, Shield, Target, Users } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function AuditDashboard({ stats, annee, missions_recentes }: any) {
    const recentes = missions_recentes ?? [];

    return (
        <AppLayout pageTitle="Audit interne IGS" breadcrumbs={[{ label: 'Audit interne' }]}>
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Inspection Générale des Services"
                    title={`Audit interne — exercice ${annee}`}
                    description="Cycle complet : plan annuel, missions, constats, recommandations, suivi de mise en œuvre. Conforme aux normes IIA/IPPF, IFACI, ISO 19011 et COSO."
                    metrics={[
                        { icon: ClipboardCheck, label: 'Plans', value: String(stats.plans_total ?? 0) },
                        { icon: Target, label: 'Missions en cours', value: String(stats.missions_en_cours ?? 0) },
                        { icon: FileWarning, label: 'Constats critiques', value: String(stats.constats_critiques ?? 0) },
                        { icon: AlertTriangle, label: 'Recommandations en retard', value: String(stats.recommandations_en_retard ?? 0) },
                    ]}
                    footer="Référentiels : IIA/IPPF · IFACI · ISO 19011 · COSO Internal Control"
                    footerIcon={Shield}
                />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Missions totales</CardTitle></CardHeader>
                        <CardContent><div className="text-3xl font-bold tabular-nums">{stats.missions_total ?? 0}</div></CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Missions clôturées</CardTitle></CardHeader>
                        <CardContent><div className="text-3xl font-bold tabular-nums text-emerald-600">{stats.missions_cloturees ?? 0}</div></CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Constats</CardTitle></CardHeader>
                        <CardContent><div className="text-3xl font-bold tabular-nums">{stats.constats_total ?? 0}</div></CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-muted-foreground">Recommandations ouvertes</CardTitle></CardHeader>
                        <CardContent><div className="text-3xl font-bold tabular-nums text-amber-600">{stats.recommandations_ouvertes ?? 0}</div></CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Users className="h-4 w-4" />Missions récentes</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Titre</TableHead>
                                    <TableHead>Plan</TableHead>
                                    <TableHead>Chef</TableHead>
                                    <TableHead>Statut</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentes.length === 0 ? (
                                    <TableRow><TableCell colSpan={5} className="py-8 text-center text-muted-foreground">Aucune mission récente.</TableCell></TableRow>
                                ) : recentes.map((m: any) => (
                                    <TableRow key={m.id}>
                                        <TableCell className="font-mono text-xs">{m.code}</TableCell>
                                        <TableCell><Link href={`/audit/missions/${m.id}`} className="font-medium hover:underline">{m.titre}</Link></TableCell>
                                        <TableCell className="text-sm">{m.plan?.annee} — {m.plan?.libelle}</TableCell>
                                        <TableCell className="text-sm">{m.chef_mission?.name ?? '—'}</TableCell>
                                        <TableCell><span className="text-xs">{m.statut}</span></TableCell>
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
