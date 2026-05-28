import { Link, router } from '@inertiajs/react';
import { BarChart3, Layers3, Package, Plus, Search, Target, TrendingUp } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function ProduitsIndex({ produits, axes, filters, statuts, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [axeId, setAxeId] = useState(String(filters.axe_id ?? 'all'));
    const [statut, setStatut] = useState(filters.statut ?? 'all');
    const produitsData = produits.data ?? [];
    const totalSousProduits = produitsData.reduce((sum: number, produit: any) => sum + Number(produit.sous_produits_count ?? 0), 0);
    const tauxMoyen = produitsData.length > 0
        ? Math.round(produitsData.reduce((sum: number, produit: any) => sum + Number(produit.taux_execution ?? 0), 0) / produitsData.length)
        : 0;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/rbm/produits', {
            q,
            axe_id: axeId === 'all' ? '' : axeId,
            statut: statut === 'all' ? '' : statut,
        }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Produits"
            breadcrumbs={[{ label: 'RBM' }, { label: 'Produits' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={produitsData} filename="produits" meta={{ total: produits.total ?? produitsData.length, filtres: filters }} />
                    <PdfExportButton reportKey="liste_produits" filtres={filters} />
                    {can.create && (
                        <Button asChild><Link href="/rbm/produits/create"><Plus className="h-4 w-4" />Nouveau Produit</Link></Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Chaîne RBM/GAR officielle"
                    title="Produits"
                    description="Déclinaison opérationnelle des axes stratégiques, avec suivi des sous-produits et de l'exécution consolidée."
                    metrics={[
                        { icon: Package, label: 'Produits', value: Number(produits.total ?? produitsData.length).toLocaleString('fr-FR') },
                        { icon: Target, label: 'Axes référencés', value: Number(axes.length ?? 0).toLocaleString('fr-FR') },
                        { icon: Layers3, label: 'Sous-produits', value: totalSousProduits.toLocaleString('fr-FR') },
                        { icon: TrendingUp, label: 'Exécution moy.', value: `${tauxMoyen}%` },
                    ]}
                    footer={`${Number(produits.total ?? produitsData.length).toLocaleString('fr-FR')} produit(s) dans le périmètre courant · Structure : Axe → Produit → Sous-Produit`}
                    footerIcon={BarChart3}
                />

                <Card>
                    <CardContent className="p-4">
                        <form onSubmit={submit} className="grid grid-cols-1 gap-3 md:grid-cols-5">
                            <div className="relative md:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Code, libellé..." className="pl-9" />
                            </div>
                            <Select value={axeId} onValueChange={setAxeId}>
                                <SelectTrigger><SelectValue placeholder="Axe" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les axes</SelectItem>
                                    {axes.map((a: any) => (<SelectItem key={a.id} value={String(a.id)}>{a.code} - {a.libelle}</SelectItem>))}
                                </SelectContent>
                            </Select>
                            <Select value={statut} onValueChange={setStatut}>
                                <SelectTrigger><SelectValue placeholder="Statut" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous statuts</SelectItem>
                                    {statuts.map((s: string) => (<SelectItem key={s} value={s}>{s}</SelectItem>))}
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
                                    <TableHead>Libellé / Axe</TableHead>
                                    <TableHead>Direction</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="text-right">Sous-Produits</TableHead>
                                    <TableHead className="w-32">Avancement</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {produitsData.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground"><Package className="mx-auto mb-2 h-8 w-8 opacity-30" />Aucun produit défini.</TableCell></TableRow>
                                ) : produitsData.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell className="font-mono text-xs font-semibold">{p.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/rbm/produits/${p.id}`} className="font-medium hover:underline">{p.libelle}</Link>
                                            <p className="text-xs text-muted-foreground">{p.axe?.code} - {p.axe?.libelle}</p>
                                        </TableCell>
                                        <TableCell className="text-xs">{p.direction ? `${p.direction.code}` : '-'}</TableCell>
                                        <TableCell><RbmStatusBadge statut={p.statut} /></TableCell>
                                        <TableCell className="text-right tabular-nums">{p.sous_produits_count}</TableCell>
                                        <TableCell>
                                            <Progress value={p.taux_execution} indicatorClassName={p.taux_execution >= 75 ? 'bg-success' : p.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'} />
                                            <p className="mt-1 text-right text-[10px] text-muted-foreground tabular-nums">{Math.round(p.taux_execution)}%</p>
                                        </TableCell>
                                        <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/rbm/produits/${p.id}`}>Voir</Link></Button></TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <Pagination pagination={produits} label="produits" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
