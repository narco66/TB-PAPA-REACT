import { Link } from '@inertiajs/react';
import { ArrowLeft, Building2, Pencil, Plus, Target, User } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickMenu } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge, TauxBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';

export default function AxeShow({ axe, can }: any) {
    return (
        <AppLayout
            pageTitle={`${axe.code} — ${axe.libelle}`}
            breadcrumbs={[{ label: 'RBM' }, { label: 'Axes', href: '/rbm/axes' }, { label: axe.code }]}
            actions={
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/rbm/axes"><ArrowLeft className="h-4 w-4" />Retour</Link>
                    </Button>
                    <PdfQuickMenu reports={[
                        { reportKey: 'fiche_performance_axe', label: 'Fiche de performance', params: { axe_id: axe.id }, description: 'Détail complet de l\'axe' },
                    ]} />
                    {can.update && (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/rbm/axes/${axe.id}/edit`}><Pencil className="h-4 w-4" />Modifier</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail Axe"
                    description="Vue d'un axe stratégique, de ses produits et de son avancement."
                />
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <span className="font-mono text-xs text-muted-foreground">{axe.code}</span>
                                    <RbmStatusBadge statut={axe.statut} />
                                </div>
                                <CardTitle className="text-xl">{axe.libelle}</CardTitle>
                                {axe.description && (
                                    <p className="mt-2 text-sm text-muted-foreground leading-relaxed">{axe.description}</p>
                                )}
                            </div>
                            <TauxBadge taux={axe.taux_execution} />
                        </div>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 border-t pt-4 md:grid-cols-4">
                        <Meta icon={Target} label="PAPA" value={axe.papa ? `${axe.papa.annee} — ${axe.papa.libelle}` : '—'} />
                        <Meta icon={Building2} label="Département" value={axe.departement ? `${axe.departement.code} — ${axe.departement.libelle}` : '—'} />
                        <Meta icon={User} label="Responsable" value={axe.responsable?.name ?? '—'} sub={axe.responsable?.fonction} />
                        <Meta icon={Target} label="Période" value={`${formatDate(axe.date_debut)} → ${formatDate(axe.date_fin)}`} />
                    </CardContent>
                    <CardContent className="border-t pt-4">
                        <div className="space-y-1.5">
                            <div className="flex justify-between text-sm">
                                <span>Avancement global de l'Axe</span>
                                <span className="tabular-nums font-medium">{Math.round(axe.taux_execution)}% (poids : {axe.poids})</span>
                            </div>
                            <Progress
                                value={axe.taux_execution}
                                indicatorClassName={axe.taux_execution >= 75 ? 'bg-success' : axe.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'}
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base">Produits rattachés ({axe.produits_count})</CardTitle>
                        {can.create_produit && (
                            <Button size="sm" asChild>
                                <Link href={`/rbm/produits/create?axe_id=${axe.id}`}><Plus className="h-4 w-4" />Nouveau Produit</Link>
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
                                {axe.produits.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">
                                            Aucun produit rattaché à cet axe.
                                        </TableCell>
                                    </TableRow>
                                ) : axe.produits.map((p: any) => (
                                    <TableRow key={p.id}>
                                        <TableCell className="font-mono text-xs">{p.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/rbm/produits/${p.id}`} className="font-medium hover:underline">{p.libelle}</Link>
                                        </TableCell>
                                        <TableCell><RbmStatusBadge statut={p.statut} /></TableCell>
                                        <TableCell>
                                            <Progress value={p.taux_execution} />
                                            <p className="mt-1 text-[10px] text-right tabular-nums text-muted-foreground">{Math.round(p.taux_execution)}%</p>
                                        </TableCell>
                                        <TableCell>
                                            <Button size="sm" variant="ghost" asChild><Link href={`/rbm/produits/${p.id}`}>Voir</Link></Button>
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

function Meta({ icon: Icon, label, value, sub }: any) {
    return (
        <div className="flex items-start gap-2">
            <Icon className="h-4 w-4 mt-0.5 text-muted-foreground" />
            <div className="min-w-0">
                <p className="text-xs text-muted-foreground">{label}</p>
                <p className="text-sm font-medium truncate">{value}</p>
                {sub && <p className="text-xs text-muted-foreground truncate">{sub}</p>}
            </div>
        </div>
    );
}
