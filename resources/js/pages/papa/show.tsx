import { Link, router } from '@inertiajs/react';
import { ArrowLeft, FileCheck2, History, Lock, Plus, ShieldCheck, Target } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickMenu } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { WorkflowActions, WorkflowTimeline, type WorkflowAction, type WorkflowHistoryItem } from '@/components/workflow/workflow-timeline';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate, formatPercent } from '@/lib/utils';

interface Props {
    papa: any;
    axes: any[];
    transitions: WorkflowAction[];
    historique: WorkflowHistoryItem[];
    can: { update: boolean; delete: boolean; create_axe: boolean };
}

export default function PapaShow({ papa, axes, transitions, historique, can }: Props) {
    const onTransition = (action: string, commentaire: string) => {
        router.post(
            `/papa/${papa.id}/workflow/${action}`,
            { commentaire },
            { preserveScroll: true },
        );
    };

    return (
        <AppLayout
            pageTitle={`PAPA ${papa.annee} — ${papa.libelle}`}
            breadcrumbs={[{ label: 'PAPA', href: '/papa' }, { label: `${papa.annee} v${papa.version}` }]}
            actions={
                <div className="flex flex-wrap gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/papa">
                            <ArrowLeft className="h-4 w-4" />
                            Retour
                        </Link>
                    </Button>
                    <PdfQuickMenu
                        reports={[
                            { reportKey: 'papa_strategique', label: 'Plan stratégique complet', params: { papa_id: papa.id }, description: 'Vue intégrale du PAPA avec axes et produits' },
                            { reportKey: 'synthese_executive', label: 'Synthèse exécutive', params: { papa_id: papa.id }, description: 'Tableau de bord pour la Présidence' },
                            { reportKey: 'matrice_rbm', label: 'Matrice RBM consolidée', params: { papa_id: papa.id }, description: 'Tous les niveaux : Axe → Tâche' },
                            { reportKey: 'matrice_indicateurs', label: 'Matrice des indicateurs KPI', params: { papa_id: papa.id } },
                            { reportKey: 'matrice_raci', label: 'Matrice RACI', params: { papa_id: papa.id } },
                        ]}
                    />
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title={`PAPA ${papa.annee} — ${papa.libelle}`}
                    description="Vue consolidée du plan, des axes, de l'exécution et des validations institutionnelles."
                />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <div className="mb-2 flex items-center gap-2">
                                        <RbmStatusBadge statut={papa.statut} />
                                        {papa.verrouille && <span className="flex items-center gap-1 text-xs text-muted-foreground"><Lock className="h-3 w-3" />Verrouillé</span>}
                                        <span className="font-mono text-xs text-muted-foreground">v{papa.version}</span>
                                    </div>
                                    <CardTitle className="text-2xl">{papa.libelle}</CardTitle>
                                    <CardDescription className="mt-1">Exercice {papa.annee}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            {papa.description && (
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Description</p>
                                    <p className="mt-1 leading-relaxed">{papa.description}</p>
                                </div>
                            )}
                            {papa.perimetre_institutionnel && (
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Périmètre institutionnel</p>
                                    <p className="mt-1 leading-relaxed">{papa.perimetre_institutionnel}</p>
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4 border-t pt-4">
                                <Meta label="Date de début" value={formatDate(papa.date_debut)} />
                                <Meta label="Date de fin" value={formatDate(papa.date_fin)} />
                                <Meta label="Date de validation" value={formatDate(papa.date_validation)} />
                                <Meta label="Clôture" value={formatDate(papa.cloture_le)} />
                                <Meta label="Validé par" value={papa.valideur?.name ?? '—'} sub={papa.valideur?.fonction} />
                                <Meta label="Créé par" value={papa.createur?.name ?? '—'} sub={papa.createur?.fonction} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-base">Exécution globale</CardTitle></CardHeader>
                        <CardContent className="space-y-5">
                            <KpiBar label="Exécution physique" value={papa.taux_execution_physique} />
                            <KpiBar label="Exécution financière" value={papa.taux_execution_financier} />
                            <div className="rounded-md bg-muted/40 p-3 text-center">
                                <p className="text-3xl font-bold tabular-nums">{papa.axes_count}</p>
                                <p className="text-xs text-muted-foreground">Axes stratégiques</p>
                            </div>
                            <Button asChild className="w-full" size="sm">
                                <Link href={`/rbm/axes?papa_id=${papa.id}`}><Target className="h-4 w-4" />Voir les axes</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="text-base flex items-center gap-2"><Target className="h-4 w-4" />Axes stratégiques du PAPA</CardTitle>
                        {can.create_axe && (
                            <Button size="sm" asChild>
                                <Link href={`/rbm/axes/create?papa_id=${papa.id}`}><Plus className="h-4 w-4" />Nouvel Axe</Link>
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
                                    <TableHead className="text-right">Poids</TableHead>
                                    <TableHead className="w-32">Avancement</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {axes.length === 0 ? (
                                    <TableRow><TableCell colSpan={6} className="py-6 text-center text-sm text-muted-foreground">Aucun axe défini pour ce PAPA.</TableCell></TableRow>
                                ) : axes.map((a: any) => (
                                    <TableRow key={a.id}>
                                        <TableCell className="font-mono text-xs font-semibold">{a.code}</TableCell>
                                        <TableCell><Link href={`/rbm/axes/${a.id}`} className="font-medium hover:underline">{a.libelle}</Link></TableCell>
                                        <TableCell><RbmStatusBadge statut={a.statut} /></TableCell>
                                        <TableCell className="text-right tabular-nums text-xs">{a.poids}</TableCell>
                                        <TableCell>
                                            <Progress value={a.taux_execution} indicatorClassName={a.taux_execution >= 75 ? 'bg-success' : a.taux_execution >= 40 ? 'bg-warning' : 'bg-destructive'} />
                                            <p className="mt-1 text-[10px] text-right text-muted-foreground tabular-nums">{Math.round(a.taux_execution)}%</p>
                                        </TableCell>
                                        <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/rbm/axes/${a.id}`}>Voir</Link></Button></TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ShieldCheck className="h-5 w-5" />
                                Workflow de validation
                            </CardTitle>
                            <CardDescription>Actions disponibles depuis l'état actuel</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkflowActions transitions={transitions} onTransition={onTransition} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <History className="h-5 w-5" />
                                Historique des transitions
                            </CardTitle>
                            <CardDescription>Visa électronique et audit trail</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <WorkflowTimeline historique={historique} />
                        </CardContent>
                    </Card>
                </div>

                {papa.verrouille && (
                    <Card className="border-warning/40 bg-warning/5">
                        <CardContent className="flex items-center gap-3 p-4">
                            <Lock className="h-5 w-5 text-warning-foreground" />
                            <div className="flex-1 text-sm">
                                <p className="font-semibold">Ce PAPA est verrouillé.</p>
                                <p className="text-muted-foreground">Les modifications ne sont plus autorisées.</p>
                            </div>
                            <FileCheck2 className="h-5 w-5 text-muted-foreground" />
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

function Meta({ label, value, sub }: any) {
    return (
        <div>
            <p className="text-xs font-medium text-muted-foreground">{label}</p>
            <p className="text-sm font-medium">{value}</p>
            {sub ? <p className="text-xs text-muted-foreground">{sub}</p> : null}
        </div>
    );
}

function KpiBar({ label, value }: any) {
    const indicator = value >= 75 ? 'bg-success' : value >= 40 ? 'bg-warning' : value > 0 ? 'bg-destructive' : '';
    return (
        <div className="space-y-1.5">
            <div className="flex justify-between text-sm">
                <span>{label}</span>
                <span className="tabular-nums font-medium">{formatPercent(value)}</span>
            </div>
            <Progress value={value} indicatorClassName={indicator} />
        </div>
    );
}
