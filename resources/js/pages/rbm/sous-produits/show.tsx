import { Link } from '@inertiajs/react';
import { ArrowLeft, BarChart3, Pencil, Plus } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge, TauxBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';

export default function SousProduitShow({ sousProduit, can }: any) {
    return (
        <AppLayout
            pageTitle={`${sousProduit.code} — ${sousProduit.libelle}`}
            breadcrumbs={[{ label: 'RBM' }, { label: 'Sous-Produits', href: '/rbm/sous-produits' }, { label: sousProduit.code }]}
            actions={
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild><Link href="/rbm/sous-produits"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                    <PdfQuickButton reportKey="fiche_sous_produit" params={{ sous_produit_id: sousProduit.id }}>Fiche PDF</PdfQuickButton>
                    {can.update && (<Button variant="outline" size="sm" asChild><Link href={`/rbm/sous-produits/${sousProduit.id}/edit`}><Pencil className="h-4 w-4" />Modifier</Link></Button>)}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail Sous-Produit"
                    description="Suivi des activités, indicateurs et livrables rattachés."
                />
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <span className="font-mono text-xs text-muted-foreground">{sousProduit.code}</span>
                                    <RbmStatusBadge statut={sousProduit.statut} />
                                </div>
                                <CardTitle className="text-xl">{sousProduit.libelle}</CardTitle>
                                <p className="text-xs text-muted-foreground mt-1">
                                    PAPA {sousProduit.produit?.axe?.papa?.annee} · Axe {sousProduit.produit?.axe?.code} ·
                                    Produit <Link href={`/rbm/produits/${sousProduit.produit?.id}`} className="text-primary hover:underline">{sousProduit.produit?.code}</Link>
                                </p>
                                {sousProduit.description && <p className="mt-2 text-sm text-muted-foreground">{sousProduit.description}</p>}
                            </div>
                            <TauxBadge taux={sousProduit.taux_execution} />
                        </div>
                    </CardHeader>
                    <CardContent className="border-t pt-4">
                        <div className="space-y-1.5">
                            <div className="flex justify-between text-sm">
                                <span>Avancement (poids : {sousProduit.poids})</span>
                                <span className="tabular-nums font-medium">{Math.round(sousProduit.taux_execution)}%</span>
                            </div>
                            <Progress value={sousProduit.taux_execution} indicatorClassName={sousProduit.taux_execution >= 75 ? 'bg-success' : sousProduit.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'} />
                        </div>
                        <p className="mt-4 text-xs text-muted-foreground">
                            Période : {formatDate(sousProduit.date_debut)} → {formatDate(sousProduit.date_fin)}
                            {sousProduit.direction ? ` · Direction : ${sousProduit.direction.code}` : ''}
                            {sousProduit.responsable ? ` · Responsable : ${sousProduit.responsable.name}` : ''}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base">Activités ({sousProduit.activites?.length ?? 0})</CardTitle>
                        {can.create_activite && (
                            <Button size="sm" asChild>
                                <Link href={`/activites/create?sous_produit_id=${sousProduit.id}`}><Plus className="h-4 w-4" />Nouvelle Activité</Link>
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="w-32">Avancement</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(sousProduit.activites ?? []).length === 0 ? (
                                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">Aucune activité.</TableCell></TableRow>
                                ) : sousProduit.activites.map((a: any) => (
                                    <TableRow key={a.id}>
                                        <TableCell className="font-mono text-xs">{a.code}</TableCell>
                                        <TableCell><Link href={`/activites/${a.id}`} className="font-medium hover:underline">{a.libelle}</Link></TableCell>
                                        <TableCell><RbmStatusBadge statut={a.statut} /></TableCell>
                                        <TableCell><Progress value={a.taux_execution} /></TableCell>
                                        <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/activites/${a.id}`}>Voir</Link></Button></TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {sousProduit.indicateurs?.length > 0 && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-base flex items-center gap-2"><BarChart3 className="h-4 w-4" />Indicateurs ({sousProduit.indicateurs.length})</CardTitle>
                            {can.create_indicateur && (
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={`/indicateurs/create?sous_produit_id=${sousProduit.id}`}><Plus className="h-4 w-4" />Ajouter un KPI</Link>
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Code</TableHead>
                                        <TableHead>Libellé</TableHead>
                                        <TableHead className="text-right">Cible</TableHead>
                                        <TableHead className="text-right">Valeur</TableHead>
                                        <TableHead className="text-right">Taux</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sousProduit.indicateurs.map((i: any) => (
                                        <TableRow key={i.id}>
                                            <TableCell className="font-mono text-xs">{i.code}</TableCell>
                                            <TableCell><Link href={`/indicateurs/${i.id}`} className="font-medium hover:underline">{i.libelle}</Link></TableCell>
                                            <TableCell className="text-right tabular-nums">{i.cible ?? '—'} {i.unite}</TableCell>
                                            <TableCell className="text-right tabular-nums">{i.valeur_actuelle ?? '—'} {i.unite}</TableCell>
                                            <TableCell className="text-right tabular-nums">{Math.round(i.taux_realisation ?? 0)}%</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
                </div>
</AppLayout>
    );
}
