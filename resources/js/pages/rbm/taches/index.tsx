import { Link, router } from '@inertiajs/react';
import { BarChart3, CheckCircle2, ListChecks, Plus, Search, TrendingUp, Users } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function TachesIndex({ taches, filters, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const tachesData = taches.data ?? [];
    const realisees = tachesData.filter((t: any) => t.statut === 'realisee').length;
    const assignees = tachesData.filter((t: any) => t.assigne_a || t.responsable).length;
    const tauxMoyen = tachesData.length > 0
        ? Math.round(tachesData.reduce((sum: number, t: any) => sum + Number(t.taux_execution ?? 0), 0) / tachesData.length)
        : 0;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/rbm/taches', { q }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Tâches"
            breadcrumbs={[{ label: 'RBM' }, { label: 'Tâches' }]}
            actions={
                <>
                    <PdfExportButton reportKey="liste_taches" filtres={filters} />
                    {can.create && (<Button asChild><Link href="/rbm/taches/create"><Plus className="h-4 w-4" />Nouvelle Tâche</Link></Button>)}
                </>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Chaîne RBM/GAR officielle"
                    title="Tâches"
                    description="Suivi fin de l'exécution opérationnelle, des responsabilités assignées et de la progression terrain."
                    metrics={[
                        { icon: ListChecks, label: 'Tâches', value: Number(taches.total ?? tachesData.length).toLocaleString('fr-FR') },
                        { icon: CheckCircle2, label: 'Réalisées visibles', value: realisees.toLocaleString('fr-FR') },
                        { icon: Users, label: 'Assignées visibles', value: assignees.toLocaleString('fr-FR') },
                        { icon: TrendingUp, label: 'Exécution moy.', value: `${tauxMoyen}%` },
                    ]}
                    footer="Niveau terminal de la chaîne RBM/GAR : Activité → Tâche"
                    footerIcon={BarChart3}
                />

                <Card>
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Code, libellé..." className="pl-9" />
                            </div>
                            <Button type="submit" variant="outline">Filtrer</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé / Activité</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Assigné à</TableHead>
                                    <TableHead className="w-32">Exécution</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {tachesData.length === 0 ? (
                                    <TableRow><TableCell colSpan={6} className="py-8 text-center text-muted-foreground"><ListChecks className="mx-auto mb-2 h-8 w-8 opacity-30" />Aucune tâche.</TableCell></TableRow>
                                ) : tachesData.map((t: any) => (
                                    <TableRow key={t.id}>
                                        <TableCell className="font-mono text-xs">{t.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/rbm/taches/${t.id}`} className="font-medium hover:underline">{t.libelle}</Link>
                                            <p className="text-xs text-muted-foreground">{t.activite?.code} · {t.activite?.libelle}</p>
                                        </TableCell>
                                        <TableCell><RbmStatusBadge statut={t.statut} /></TableCell>
                                        <TableCell className="text-xs">{t.assigne_a?.name ?? t.responsable?.name ?? '-'}</TableCell>
                                        <TableCell>
                                            <Progress value={t.taux_execution} indicatorClassName={t.taux_execution >= 75 ? 'bg-success' : t.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'} />
                                            <p className="mt-1 text-right text-[10px] text-muted-foreground tabular-nums">{Math.round(t.taux_execution)}%</p>
                                        </TableCell>
                                        <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/rbm/taches/${t.id}`}>Voir</Link></Button></TableCell>
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
