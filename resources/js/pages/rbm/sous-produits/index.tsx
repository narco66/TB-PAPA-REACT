import { Link, router } from '@inertiajs/react';
import { BarChart3, Boxes, ListChecks, Package, Plus, Search, TrendingUp } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function SousProduitsIndex({ sousProduits, produits, filters, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [pid, setPid] = useState(String(filters.produit_id ?? 'all'));
    const sousProduitsData = sousProduits.data ?? [];
    const totalActivites = sousProduitsData.reduce((sum: number, sp: any) => sum + Number(sp.activites_count ?? 0), 0);
    const totalKpi = sousProduitsData.reduce((sum: number, sp: any) => sum + Number(sp.indicateurs_count ?? 0), 0);
    const tauxMoyen = sousProduitsData.length > 0
        ? Math.round(sousProduitsData.reduce((sum: number, sp: any) => sum + Number(sp.taux_execution ?? 0), 0) / sousProduitsData.length)
        : 0;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/rbm/sous-produits', { q, produit_id: pid === 'all' ? '' : pid }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Sous-Produits"
            breadcrumbs={[{ label: 'RBM' }, { label: 'Sous-Produits' }]}
            actions={
                <>
                    <PdfExportButton reportKey="liste_sous_produits" filtres={filters} />
                    {can.create && (<Button asChild><Link href="/rbm/sous-produits/create"><Plus className="h-4 w-4" />Nouveau Sous-Produit</Link></Button>)}
                </>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Chaîne RBM/GAR officielle"
                    title="Sous-Produits"
                    description="Niveau de résultat intermédiaire reliant les produits aux activités, indicateurs et livrables de suivi."
                    metrics={[
                        { icon: Boxes, label: 'Sous-produits', value: Number(sousProduits.total ?? sousProduitsData.length).toLocaleString('fr-FR') },
                        { icon: Package, label: 'Produits', value: Number(produits.length ?? 0).toLocaleString('fr-FR') },
                        { icon: ListChecks, label: 'Activités visibles', value: totalActivites.toLocaleString('fr-FR') },
                        { icon: TrendingUp, label: 'Exécution moy.', value: `${tauxMoyen}%` },
                    ]}
                    footer={`${totalKpi.toLocaleString('fr-FR')} indicateur(s) rattaché(s) aux sous-produits affichés`}
                    footerIcon={BarChart3}
                />

                <Card>
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div className="relative md:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Code, libellé..." className="pl-9" />
                            </div>
                            <Select value={pid} onValueChange={setPid}>
                                <SelectTrigger><SelectValue placeholder="Produit" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les produits</SelectItem>
                                    {produits.map((p: any) => (<SelectItem key={p.id} value={String(p.id)}>{p.code} - {p.libelle}</SelectItem>))}
                                </SelectContent>
                            </Select>
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
                                    <TableHead>Libellé / Parent</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="text-right">Activités</TableHead>
                                    <TableHead className="text-right">KPI</TableHead>
                                    <TableHead className="w-32">Avancement</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sousProduitsData.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground"><Boxes className="mx-auto mb-2 h-8 w-8 opacity-30" />Aucun sous-produit défini.</TableCell></TableRow>
                                ) : sousProduitsData.map((sp: any) => (
                                    <TableRow key={sp.id}>
                                        <TableCell className="font-mono text-xs font-semibold">{sp.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/rbm/sous-produits/${sp.id}`} className="font-medium hover:underline">{sp.libelle}</Link>
                                            <p className="text-xs text-muted-foreground">{sp.produit?.axe?.code} / {sp.produit?.code}</p>
                                        </TableCell>
                                        <TableCell><RbmStatusBadge statut={sp.statut} /></TableCell>
                                        <TableCell className="text-right tabular-nums">{sp.activites_count}</TableCell>
                                        <TableCell className="text-right tabular-nums">{sp.indicateurs_count}</TableCell>
                                        <TableCell>
                                            <Progress value={sp.taux_execution} indicatorClassName={sp.taux_execution >= 75 ? 'bg-success' : sp.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'} />
                                            <p className="mt-1 text-right text-[10px] text-muted-foreground tabular-nums">{Math.round(sp.taux_execution)}%</p>
                                        </TableCell>
                                        <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/rbm/sous-produits/${sp.id}`}>Voir</Link></Button></TableCell>
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
