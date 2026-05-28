import { Link } from '@inertiajs/react';
import { ClipboardCheck, Plus } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function PlansIndex({ plans, can }: any) {
    const items = plans.data ?? [];

    return (
        <AppLayout
            pageTitle="Plans d'audit"
            breadcrumbs={[{ label: 'Audit interne', href: '/audit' }, { label: "Plans d'audit" }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={items} filename="plans_audit" meta={{ total: plans.total ?? items.length }} />
                    <PdfExportButton reportKey="liste_audit_plans" />
                    {can.create && (
                        <Button asChild><Link href="/audit/plans/create"><Plus className="h-4 w-4" />Nouveau plan</Link></Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Cycle d'audit annuel"
                    title="Plans d'audit"
                    description="Programmation annuelle des missions d'audit interne, validée par la Direction générale. Conforme IIA/IPPF Standard 2010."
                    metrics={[
                        { icon: ClipboardCheck, label: 'Plans', value: String(plans.total ?? items.length) },
                    ]}
                />

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Année</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="text-right">Missions</TableHead>
                                    <TableHead>Période</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={6} className="py-8 text-center text-muted-foreground"><ClipboardCheck className="mx-auto mb-2 h-8 w-8 opacity-30" />Aucun plan d'audit.</TableCell></TableRow>
                                ) : items.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell className="font-semibold tabular-nums">{p.annee}</TableCell>
                                        <TableCell>
                                            <Link href={`/audit/plans/${p.id}`} className="font-medium hover:underline">{p.libelle}</Link>
                                        </TableCell>
                                        <TableCell><span className="text-xs">{p.statut}</span></TableCell>
                                        <TableCell className="text-right tabular-nums">{p.missions_count}</TableCell>
                                        <TableCell className="text-xs text-muted-foreground">
                                            {p.date_debut ? new Date(p.date_debut).toLocaleDateString('fr-FR') : '—'}
                                            {' → '}
                                            {p.date_fin ? new Date(p.date_fin).toLocaleDateString('fr-FR') : '—'}
                                        </TableCell>
                                        <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/audit/plans/${p.id}`}>Voir</Link></Button></TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <Pagination pagination={plans} label="plans d'audit" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
