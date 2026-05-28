import { Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Download, Lock, Pencil, Upload } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/utils';

export default function ExerciceShow({ exercice, can }: any) {
    const confirmer = (msg: string, action: () => void) => { if (confirm(msg)) action(); };

    return (
        <AppLayout
            pageTitle={`Exercice ${exercice.annee}`}
            breadcrumbs={[{ label: 'Budget', href: '/budget' }, { label: 'Exercices', href: '/budget/exercices' }, { label: String(exercice.annee) }]}
            actions={
                <div className="flex flex-wrap gap-2">
                    <Button variant="ghost" size="sm" asChild><Link href="/budget/exercices"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                    {can.update && (<Button variant="outline" size="sm" asChild><Link href={`/budget/exercices/${exercice.id}/edit`}><Pencil className="h-4 w-4" />Modifier</Link></Button>)}
                    {can.import && (<Button variant="outline" size="sm" asChild><Link href="/budget/imports"><Upload className="h-4 w-4" />Importer</Link></Button>)}
                    {can.export && (<Button variant="outline" size="sm" asChild><a href={`/budget/exports?exercice_id=${exercice.id}&template=consolide`}><Download className="h-4 w-4" />Exporter</a></Button>)}
                    {can.validate && exercice.statut !== 'valide' && (
                        <Button size="sm" variant="success" onClick={() => confirmer('Valider l\'exercice ?', () => router.post(`/budget/exercices/${exercice.id}/valider`))}>
                            <CheckCircle2 className="h-4 w-4" />Valider
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail exercice budgétaire"
                    description="Synthèse des lignes, sources et masses budgétaires de l'exercice."
                />
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="mb-2 flex items-center gap-2">
                                    <RbmStatusBadge statut={exercice.statut} />
                                    {['cloture', 'archive'].includes(exercice.statut) && <Lock className="h-3.5 w-3.5 text-muted-foreground" />}
                                </div>
                                <CardTitle className="text-2xl">{exercice.libelle}</CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">Exercice {exercice.annee} · Devise {exercice.devise}</p>
                                {exercice.description && <p className="mt-2 text-sm text-muted-foreground">{exercice.description}</p>}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 border-t pt-4 md:grid-cols-4">
                        <Meta label="Date début" value={formatDate(exercice.date_debut)} />
                        <Meta label="Date fin" value={formatDate(exercice.date_fin)} />
                        <Meta label="Créé par" value={exercice.createur?.name ?? '—'} sub={exercice.createur?.fonction} />
                        <Meta label="Validé par" value={exercice.valideur?.name ?? '—'} sub={exercice.valide_at ? formatDate(exercice.valide_at) : ''} />
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle className="text-base">Recettes</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <KV label="Total recettes" value={exercice.total_recettes} bold />
                            <KV label="Recettes internes (États membres)" value={exercice.total_recettes_internes} />
                            <KV label="Recettes externes (PTF / Dons)" value={exercice.total_recettes_externes} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-base">Dépenses</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <KV label="Total dépenses" value={exercice.total_depenses} bold />
                            <KV label="CEEAC-EM" value={exercice.total_depenses_ceeac_em} />
                            <KV label="PTF" value={exercice.total_depenses_ptf} />
                            <div className="border-t pt-2" />
                            <KV label="Fonctionnement" value={exercice.total_fonctionnement} />
                            <KV label="Investissement" value={exercice.total_investissement} />
                            <KV label="Équipement" value={exercice.total_equipement} />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardContent className="flex flex-wrap items-center justify-between gap-2 p-4">
                        <p className="text-sm">
                            <span className="font-semibold tabular-nums">{exercice.lignes_count}</span> lignes budgétaires.
                        </p>
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/budget/lignes?exercice_id=${exercice.id}`}>Voir toutes les lignes</Link>
                        </Button>
                    </CardContent>
                </Card>
                </div>
</AppLayout>
    );
}

function Meta({ label, value, sub }: any) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-sm font-medium">{value}</p>
            {sub ? <p className="text-xs text-muted-foreground">{sub}</p> : null}
        </div>
    );
}

function KV({ label, value, bold }: any) {
    return (
        <div className="flex justify-between">
            <span className={bold ? 'font-semibold' : ''}>{label}</span>
            <span className={`tabular-nums ${bold ? 'font-bold' : ''}`}>{formatCurrency(value)}</span>
        </div>
    );
}
