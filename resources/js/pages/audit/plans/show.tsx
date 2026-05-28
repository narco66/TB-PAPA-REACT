import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Plus, Target } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function PlanShow({ plan, missions, can }: any) {
    const items = missions ?? [];

    const valider = () => {
        if (confirm(`Valider le plan d'audit ${plan.annee} ?`)) {
            router.post(`/audit/plans/${plan.id}/valider`);
        }
    };

    return (
        <AppLayout
            pageTitle={`Plan d'audit ${plan.annee}`}
            breadcrumbs={[{ label: 'Audit interne', href: '/audit' }, { label: 'Plans', href: '/audit/plans' }, { label: String(plan.annee) }]}
            actions={(
                <div className="flex items-center gap-2">
                    {can.validate && plan.statut === 'soumis' && (
                        <Button onClick={valider}><CheckCircle2 className="h-4 w-4" />Valider le plan</Button>
                    )}
                    {can.mission_create && (
                        <Button asChild variant="outline"><Link href={`/audit/missions/create?plan_id=${plan.id}`}><Plus className="h-4 w-4" />Mission</Link></Button>
                    )}
                </div>
            )}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow={`Statut : ${plan.statut}`}
                    title={`${plan.libelle}`}
                    description={plan.description ?? "Plan d'audit annuel IGS"}
                    metrics={[
                        { icon: Target, label: 'Missions', value: String(plan.missions_count ?? 0) },
                    ]}
                />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/audit/plans"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                </div>

                {plan.orientation_strategique && (
                    <Card>
                        <CardHeader><CardTitle>Orientation stratégique</CardTitle></CardHeader>
                        <CardContent><p className="whitespace-pre-line text-sm">{plan.orientation_strategique}</p></CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader><CardTitle>Missions du plan</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Titre</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Chef</TableHead>
                                    <TableHead className="text-right">Constats</TableHead>
                                    <TableHead className="text-right">Recos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground">Aucune mission rattachée à ce plan.</TableCell></TableRow>
                                ) : items.map((m: any) => (
                                    <TableRow key={m.id}>
                                        <TableCell className="font-mono text-xs">{m.code}</TableCell>
                                        <TableCell><Link href={`/audit/missions/${m.id}`} className="font-medium hover:underline">{m.titre}</Link></TableCell>
                                        <TableCell className="text-xs">{m.type}</TableCell>
                                        <TableCell className="text-xs">{m.statut}</TableCell>
                                        <TableCell className="text-sm">{m.chef_mission?.name ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums">{m.constats_count}</TableCell>
                                        <TableCell className="text-right tabular-nums">{m.recommandations_count}</TableCell>
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
