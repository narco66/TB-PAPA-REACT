import { Link, router } from '@inertiajs/react';
import { BarChart3, Building2, Layers3, Plus, Search, Target, TrendingUp } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function AxesIndex({ axes, papas, filters, statuts, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [papaId, setPapaId] = useState(String(filters.papa_id ?? 'all'));
    const [statut, setStatut] = useState(filters.statut ?? 'all');
    const axesData = axes.data ?? [];
    const totalProduits = axesData.reduce((sum: number, axe: any) => sum + Number(axe.produits_count ?? 0), 0);
    const tauxMoyen = axesData.length > 0
        ? Math.round(axesData.reduce((sum: number, axe: any) => sum + Number(axe.taux_execution ?? 0), 0) / axesData.length)
        : 0;
    const filtreActif = Boolean(q || papaId !== 'all' || statut !== 'all');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/rbm/axes', {
            q,
            papa_id: papaId === 'all' ? '' : papaId,
            statut: statut === 'all' ? '' : statut,
        }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Axes stratégiques"
            breadcrumbs={[{ label: 'RBM' }, { label: 'Axes' }]}
            actions={
                <>
                    <PdfExportButton reportKey="liste_axes" filtres={filters} />
                    {can.create && (
                        <Button asChild>
                            <Link href="/rbm/axes/create"><Plus className="h-4 w-4" />Nouvel Axe</Link>
                        </Button>
                    )}
                </>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Chaîne RBM/GAR officielle"
                    title="Axes stratégiques"
                    description="Pilotage des priorités institutionnelles du PAPA, de l'axe au produit, avec suivi de l'exécution et des responsabilités."
                    metrics={[
                        { icon: Target, label: filtreActif ? 'Axes filtrés' : 'Axes', value: Number(axes.total ?? axesData.length).toLocaleString('fr-FR') },
                        { icon: Building2, label: 'PAPA suivis', value: Number(papas.length ?? 0).toLocaleString('fr-FR') },
                        { icon: Layers3, label: 'Produits visibles', value: totalProduits.toLocaleString('fr-FR') },
                        { icon: TrendingUp, label: 'Exécution moy.', value: `${tauxMoyen}%` },
                    ]}
                    footer={`${Number(axes.total ?? axesData.length).toLocaleString('fr-FR')} axe(s) dans le périmètre courant · Structure : Axe → Produit → Sous-Produit → Activité → Tâche`}
                    footerIcon={BarChart3}
                />

                <Card>
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="grid grid-cols-1 gap-3 md:grid-cols-5">
                            <div className="relative md:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Code, libellé..." className="pl-9" />
                            </div>
                            <Select value={papaId} onValueChange={setPapaId}>
                                <SelectTrigger><SelectValue placeholder="PAPA" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les PAPA</SelectItem>
                                    {papas.map((p: any) => (
                                        <SelectItem key={p.id} value={String(p.id)}>{p.annee}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={statut} onValueChange={setStatut}>
                                <SelectTrigger><SelectValue placeholder="Statut" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous statuts</SelectItem>
                                    {statuts.map((s: string) => (
                                        <SelectItem key={s} value={s}>{s}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button type="submit" variant="outline">Filtrer</Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="border-b">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Target className="h-5 w-5 text-primary" />
                            Portefeuille des axes
                        </CardTitle>
                        <CardDescription>Vue consolidée des axes stratégiques, de leur statut et de leur avancement.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé / PAPA</TableHead>
                                    <TableHead>Département</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="text-right">Produits</TableHead>
                                    <TableHead className="w-36">Avancement</TableHead>
                                    <TableHead className="text-right">Poids</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {axesData.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="py-10 text-center text-muted-foreground">
                                            <Target className="mx-auto mb-2 h-8 w-8 opacity-30" />
                                            Aucun axe stratégique défini.
                                        </TableCell>
                                    </TableRow>
                                ) : axesData.map((a: any) => (
                                    <TableRow key={a.id}>
                                        <TableCell className="font-mono text-xs font-semibold">{a.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/rbm/axes/${a.id}`} className="font-medium hover:underline">{a.libelle}</Link>
                                            <p className="text-xs text-muted-foreground">PAPA {a.papa?.annee}</p>
                                        </TableCell>
                                        <TableCell className="text-xs">
                                            {a.departement ? `${a.departement.code} - ${a.departement.libelle}` : '-'}
                                        </TableCell>
                                        <TableCell><RbmStatusBadge statut={a.statut} /></TableCell>
                                        <TableCell className="text-right tabular-nums">{a.produits_count}</TableCell>
                                        <TableCell>
                                            <Progress
                                                value={a.taux_execution}
                                                indicatorClassName={a.taux_execution >= 75 ? 'bg-success' : a.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'}
                                            />
                                            <p className="mt-1 text-right text-[10px] text-muted-foreground tabular-nums">{Math.round(a.taux_execution)}%</p>
                                        </TableCell>
                                        <TableCell className="text-right text-xs tabular-nums">{a.poids}</TableCell>
                                        <TableCell>
                                            <Button size="sm" variant="ghost" asChild><Link href={`/rbm/axes/${a.id}`}>Voir</Link></Button>
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
