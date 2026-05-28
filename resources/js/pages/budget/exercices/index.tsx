import { Link } from '@inertiajs/react';
import { Calendar, FileText, Plus, TrendingUp, Wallet } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/lib/utils';

export default function ExercicesIndex({ exercices, can }: any) {
    const exercicesData = exercices.data ?? [];
    const totalLignes = exercicesData.reduce((sum: number, exercice: any) => sum + Number(exercice.lignes_count ?? 0), 0);
    const totalBudget = exercicesData.reduce((sum: number, exercice: any) => sum + Number(exercice.total_depenses ?? 0), 0);

    return (
        <AppLayout
            pageTitle="Exercices budgétaires"
            breadcrumbs={[{ label: 'Budget', href: '/budget' }, { label: 'Exercices' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={exercicesData} filename="exercices_budgetaires" meta={{ total: exercices.total ?? exercicesData.length }} />
                    <PdfExportButton reportKey="liste_budget_exercices" />
                    {can.create && (
                        <Button asChild><Link href="/budget/exercices/create"><Plus className="h-4 w-4" />Nouvel exercice</Link></Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="Pilotage budgétaire"
                title="Exercices budgétaires"
                description="Cadre annuel de planification, programmation et suivi des enveloppes budgétaires CEEAC-EM et partenaires."
                metrics={[
                    { icon: Calendar, label: 'Exercices', value: Number(exercices.total ?? exercicesData.length).toLocaleString('fr-FR') },
                    { icon: FileText, label: 'Lignes visibles', value: totalLignes.toLocaleString('fr-FR') },
                    { icon: Wallet, label: 'Budget visible', value: totalBudget.toLocaleString('fr-FR') },
                    { icon: TrendingUp, label: 'Sources', value: 'CEEAC/PTF' },
                ]}
                footer="Vue consolidée des exercices et masses budgétaires"
                footerIcon={Wallet}
            />

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Année</TableHead>
                                <TableHead>Libellé</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead className="text-right">Lignes</TableHead>
                                <TableHead className="text-right">Budget total</TableHead>
                                <TableHead className="text-right">CEEAC-EM</TableHead>
                                <TableHead className="text-right">PTF</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {exercicesData.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="py-8 text-center text-muted-foreground"><Wallet className="mx-auto mb-2 h-8 w-8 opacity-30" />Aucun exercice budgétaire.</TableCell></TableRow>
                            ) : exercicesData.map((e: any) => (
                                <TableRow key={e.id}>
                                    <TableCell className="font-semibold tabular-nums">{e.annee}</TableCell>
                                    <TableCell>
                                        <Link href={`/budget/exercices/${e.id}`} className="font-medium hover:underline">{e.libelle}</Link>
                                        {e.description && <p className="text-xs text-muted-foreground line-clamp-1">{e.description}</p>}
                                    </TableCell>
                                    <TableCell><RbmStatusBadge statut={e.statut} /></TableCell>
                                    <TableCell className="text-right tabular-nums">{e.lignes_count}</TableCell>
                                    <TableCell className="text-right tabular-nums text-sm">{formatCurrency(e.total_depenses)}</TableCell>
                                    <TableCell className="text-right tabular-nums text-xs">{formatCurrency(e.total_depenses_ceeac_em)}</TableCell>
                                    <TableCell className="text-right tabular-nums text-xs">{formatCurrency(e.total_depenses_ptf)}</TableCell>
                                    <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/budget/exercices/${e.id}`}>Voir</Link></Button></TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <Pagination pagination={exercices} label="exercices" />
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}
