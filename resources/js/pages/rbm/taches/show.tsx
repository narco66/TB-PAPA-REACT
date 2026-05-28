import { Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge, TauxBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { formatDate } from '@/lib/utils';

export default function TacheShow({ tache, can }: any) {
    return (
        <AppLayout
            pageTitle={`${tache.code} — ${tache.libelle}`}
            breadcrumbs={[{ label: 'RBM' }, { label: 'Tâches', href: '/rbm/taches' }, { label: tache.code }]}
            actions={
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild><Link href="/rbm/taches"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                    <PdfQuickButton reportKey="fiche_tache" params={{ tache_id: tache.id }}>Fiche PDF</PdfQuickButton>
                    {can.update && (<Button variant="outline" size="sm" asChild><Link href={`/rbm/taches/${tache.id}/edit`}><Pencil className="h-4 w-4" />Modifier</Link></Button>)}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail Tâche"
                    description="Consultation de l'exécution, des responsables et échéances de la tâche."
                />
            <Card>
                <CardHeader>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <div className="mb-2 flex items-center gap-2">
                                <span className="font-mono text-xs text-muted-foreground">{tache.code}</span>
                                <RbmStatusBadge statut={tache.statut} />
                            </div>
                            <CardTitle className="text-xl">{tache.libelle}</CardTitle>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Activité : <Link href={`/activites/${tache.activite?.id}`} className="text-primary hover:underline">{tache.activite?.code}</Link>
                                {tache.activite?.sous_produit && ` · Sous-Produit : ${tache.activite.sous_produit.code}`}
                            </p>
                            {tache.description && <p className="mt-2 text-sm text-muted-foreground leading-relaxed">{tache.description}</p>}
                        </div>
                        <TauxBadge taux={tache.taux_execution} />
                    </div>
                </CardHeader>
                <CardContent className="border-t pt-4 space-y-4">
                    <div className="space-y-1.5">
                        <div className="flex justify-between text-sm">
                            <span>Taux d'exécution (poids : {tache.poids})</span>
                            <span className="tabular-nums font-medium">{Math.round(tache.taux_execution)}%</span>
                        </div>
                        <Progress value={tache.taux_execution} indicatorClassName={tache.taux_execution >= 75 ? 'bg-success' : tache.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'} />
                    </div>
                    <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <p className="text-xs text-muted-foreground">Période</p>
                            <p>{formatDate(tache.date_debut)} → {formatDate(tache.date_fin)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Responsabilités</p>
                            <p>Responsable : {tache.responsable?.name ?? '—'}</p>
                            <p>Assignée à : {tache.assigne_a?.name ?? '—'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
                </div>
</AppLayout>
    );
}
