import { Link } from '@inertiajs/react';
import { ArrowLeft, Pencil, Plus } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge, TauxBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';

export default function ProduitShow({ produit, can }: any) {
    return (
        <AppLayout
            pageTitle={`${produit.code} — ${produit.libelle}`}
            breadcrumbs={[
                { label: 'RBM' },
                { label: 'Produits', href: '/rbm/produits' },
                { label: produit.code },
            ]}
            actions={
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild><Link href="/rbm/produits"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                    <PdfQuickButton reportKey="fiche_produit" params={{ produit_id: produit.id }}>Fiche PDF</PdfQuickButton>
                    {can.update && (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/rbm/produits/${produit.id}/edit`}><Pencil className="h-4 w-4" />Modifier</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail Produit"
                    description="Suivi du produit, des sous-produits et de l'exécution associée."
                />
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <span className="font-mono text-xs text-muted-foreground">{produit.code}</span>
                                    <RbmStatusBadge statut={produit.statut} />
                                </div>
                                <CardTitle className="text-xl">{produit.libelle}</CardTitle>
                                <p className="text-sm text-muted-foreground mt-1">
                                    Axe : <Link href={`/rbm/axes/${produit.axe?.id}`} className="text-primary hover:underline">{produit.axe?.code} — {produit.axe?.libelle}</Link>
                                </p>
                                {produit.description && <p className="mt-2 text-sm text-muted-foreground leading-relaxed">{produit.description}</p>}
                            </div>
                            <TauxBadge taux={produit.taux_execution} />
                        </div>
                    </CardHeader>
                    <CardContent className="border-t pt-4">
                        <div className="space-y-1.5">
                            <div className="flex justify-between text-sm">
                                <span>Avancement du Produit (poids : {produit.poids})</span>
                                <span className="tabular-nums font-medium">{Math.round(produit.taux_execution)}%</span>
                            </div>
                            <Progress value={produit.taux_execution} indicatorClassName={produit.taux_execution >= 75 ? 'bg-success' : produit.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'} />
                        </div>
                        <div className="mt-4 text-xs text-muted-foreground">
                            Période : {formatDate(produit.date_debut)} → {formatDate(produit.date_fin)}
                            {produit.direction ? ` · Direction : ${produit.direction.code}` : ''}
                            {produit.responsable ? ` · Responsable : ${produit.responsable.name}` : ''}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base">Sous-Produits ({produit.sous_produits?.length ?? 0})</CardTitle>
                        {can.create_sous_produit && (
                            <Button size="sm" asChild>
                                <Link href={`/rbm/sous-produits/create?produit_id=${produit.id}`}><Plus className="h-4 w-4" />Nouveau Sous-Produit</Link>
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
                                {(produit.sous_produits ?? []).length === 0 ? (
                                    <TableRow><TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">Aucun sous-produit défini.</TableCell></TableRow>
                                ) : produit.sous_produits.map((sp: any) => (
                                    <TableRow key={sp.id}>
                                        <TableCell className="font-mono text-xs">{sp.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/rbm/sous-produits/${sp.id}`} className="font-medium hover:underline">{sp.libelle}</Link>
                                        </TableCell>
                                        <TableCell><RbmStatusBadge statut={sp.statut} /></TableCell>
                                        <TableCell><Progress value={sp.taux_execution} /></TableCell>
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
