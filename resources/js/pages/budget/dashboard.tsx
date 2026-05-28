import { Link, router } from '@inertiajs/react';
import { Building2, ListChecks, PiggyBank, Target, TrendingDown, TrendingUp, Wallet } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency, formatPercent } from '@/lib/utils';

const TYPE_LIBELLES: Record<string, string> = {
    recette_interne: 'Recettes internes (États membres)',
    recette_externe: 'Recettes externes (PTF)',
    fonctionnement: 'Dépenses de fonctionnement',
    investissement: 'Dépenses d\'investissement',
    equipement: 'Dépenses d\'équipement',
    dotation: 'Dotations institutions spécialisées',
    dette: 'Remboursement de dette',
    transfert: 'Transferts',
};

export default function BudgetDashboard({ exercices, exercice, synthese, repartitionSource, repartitionType, repartitionPilier, avancementAxes }: any) {
    if (!exercice) {
        return (
            <AppLayout pageTitle="Tableau de bord budgétaire" breadcrumbs={[{ label: 'Budget' }]}>
                <div className="space-y-6">
                    <InstitutionalHero
                        title="Tableau budgétaire"
                        description="Pilotage des enveloppes, sources de financement et exécution budgétaire."
                    />
                    <Card>
                        <CardContent className="p-8 text-center text-muted-foreground">
                            Aucun exercice budgétaire configuré.{' '}
                            <Link href="/budget/exercices/create" className="text-primary hover:underline">Créer un exercice</Link>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout
            pageTitle={`Budget ${exercice.annee}`}
            breadcrumbs={[{ label: 'Budget' }, { label: 'Tableau de bord' }]}
            actions={
                <div className="flex gap-2">
                    <PdfQuickButton reportKey="budget_consolide" params={{ exercice_id: exercice.id }}>Budget PDF</PdfQuickButton>
                    <Select value={String(exercice.id)} onValueChange={(v) => router.get('/budget', { exercice_id: v })}>
                        <SelectTrigger className="w-64"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            {exercices.map((e: any) => (
                                <SelectItem key={e.id} value={String(e.id)}>{e.annee} — {e.libelle}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Tableau budgétaire"
                    description="Pilotage des enveloppes, sources de financement et exécution budgétaire."
                    metrics={[
                        { icon: Wallet, label: 'Budget prévu', value: formatCurrency(synthese.total_prevu) },
                        { icon: PiggyBank, label: 'Engagé', value: formatCurrency(synthese.total_engage) },
                        { icon: TrendingUp, label: 'Engagement', value: formatPercent(synthese.taux_engagement) },
                        { icon: TrendingDown, label: 'Disponible', value: formatCurrency(synthese.disponible) },
                    ]}
                    footer={`Exercice ${exercice.annee} · Devise ${exercice.devise}`}
                    footerIcon={Wallet}
                />

                {/* En-tête exercice */}
                <Card className="border-primary/30 bg-gradient-to-r from-primary/5 to-transparent">
                    <CardContent className="flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="mb-2 flex items-center gap-2">
                                <p className="text-xs font-semibold uppercase tracking-wider text-primary">Exercice budgétaire</p>
                                <RbmStatusBadge statut={exercice.statut} />
                            </div>
                            <Link href={`/budget/exercices/${exercice.id}`} className="text-2xl font-bold hover:underline">
                                {exercice.libelle}
                            </Link>
                            <p className="text-sm text-muted-foreground">Année {exercice.annee} · Devise {exercice.devise}</p>
                        </div>
                        <div className="flex items-center gap-6">
                            <div>
                                <p className="text-xs text-muted-foreground">Engagé</p>
                                <p className="text-xl font-bold tabular-nums">{formatPercent(synthese.taux_engagement)}</p>
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Consommé</p>
                                <p className="text-xl font-bold tabular-nums">{formatPercent(synthese.taux_consommation)}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* KPI principaux */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <KpiCard
                        title="Budget total"
                        value={formatCurrency(synthese.total)}
                        sub={`Recettes : ${formatCurrency(synthese.total_recettes)}`}
                        icon={Wallet}
                        bg="bg-blue-100 text-blue-700"
                    />
                    <KpiCard
                        title="CEEAC-EM (États membres)"
                        value={formatCurrency(synthese.total_ceeac_em)}
                        sub={`${formatPercent(synthese.total > 0 ? (synthese.total_ceeac_em / synthese.total) * 100 : 0)} du total`}
                        icon={Building2}
                        bg="bg-emerald-100 text-emerald-700"
                    />
                    <KpiCard
                        title="Partenaires (PTF)"
                        value={formatCurrency(synthese.total_ptf)}
                        sub={`${formatPercent(synthese.total > 0 ? (synthese.total_ptf / synthese.total) * 100 : 0)} du total`}
                        icon={PiggyBank}
                        bg="bg-amber-100 text-amber-700"
                    />
                    <KpiCard
                        title="Disponible"
                        value={formatCurrency(synthese.disponible)}
                        sub={`Engagé : ${formatCurrency(synthese.engage)}`}
                        icon={ListChecks}
                        bg="bg-violet-100 text-violet-700"
                    />
                </div>

                {/* Répartition par type */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base">Répartition par type de dépense</CardTitle>
                            <CardDescription>Ventilation CEEAC-EM / PTF des dépenses publiques</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {repartitionType.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Aucune dépense saisie.</p>
                            ) : repartitionType.map((r: any) => {
                                const pct = synthese.total > 0 ? (r.total / synthese.total) * 100 : 0;
                                return (
                                    <div key={r.type} className="space-y-1.5">
                                        <div className="flex items-center justify-between text-sm">
                                            <span>{TYPE_LIBELLES[r.type] ?? r.type}</span>
                                            <span className="tabular-nums font-medium">{formatCurrency(r.total)}</span>
                                        </div>
                                        <Progress value={pct} />
                                        <div className="flex justify-between text-[10px] text-muted-foreground tabular-nums">
                                            <span>CEEAC-EM : {formatCurrency(r.ceeac_em)}</span>
                                            <span>PTF : {formatCurrency(r.ptf)}</span>
                                            <span>{pct.toFixed(1)}%</span>
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Recettes vs Dépenses</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <Balance label="Recettes internes (États membres)" value={synthese.total_recettes_internes} icon={TrendingUp} color="text-success" />
                            <Balance label="Recettes externes (PTF)" value={synthese.total_recettes_externes} icon={TrendingUp} color="text-emerald-600" />
                            <div className="border-t pt-3" />
                            <Balance label="Fonctionnement" value={synthese.fonctionnement} icon={TrendingDown} color="text-orange-600" />
                            <Balance label="Investissement" value={synthese.investissement} icon={TrendingDown} color="text-purple-600" />
                            <Balance label="Équipement" value={synthese.equipement} icon={TrendingDown} color="text-blue-600" />
                            <div className="border-t pt-3 flex justify-between font-semibold">
                                <span>Solde budgétaire</span>
                                <span className="tabular-nums">{formatCurrency(synthese.total_recettes - synthese.total)}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Piliers + Axes */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {repartitionPilier.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base flex items-center gap-2"><Target className="h-4 w-4" />Plan Annuel de Performance — par Pilier</CardTitle>
                                <CardDescription>Répartition CEEAC-EM / PTF</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {repartitionPilier.map((p: any) => (
                                    <div key={p.pilier} className="space-y-1">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="font-medium">{p.pilier}</span>
                                            <span className="tabular-nums">{formatCurrency(p.total)}</span>
                                        </div>
                                        <Progress value={synthese.total > 0 ? (p.total / synthese.total) * 100 : 0} />
                                        <div className="flex justify-between text-[10px] text-muted-foreground tabular-nums">
                                            <span>CEEAC-EM : {formatCurrency(p.ceeac_em)}</span>
                                            <span>PTF : {formatCurrency(p.ptf)}</span>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {avancementAxes.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Top 10 Axes — Exécution budgétaire</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {avancementAxes.map((a: any, i: number) => (
                                    <div key={i} className="space-y-1">
                                        <div className="flex items-center justify-between text-xs">
                                            <span className="line-clamp-1 font-medium">{a.axe}</span>
                                            <Badge variant="outline" className="tabular-nums">{formatPercent(a.taux_engagement)}</Badge>
                                        </div>
                                        <Progress value={a.taux_engagement} indicatorClassName={a.taux_engagement >= 75 ? 'bg-success' : a.taux_engagement >= 40 ? 'bg-warning' : 'bg-destructive'} />
                                        <div className="flex justify-between text-[10px] text-muted-foreground tabular-nums">
                                            <span>{formatCurrency(a.total)}</span>
                                            <span>Engagé : {formatCurrency(a.engage)}</span>
                                            <span>Payé : {formatCurrency(a.paye)}</span>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Top sources de financement */}
                {repartitionSource.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Sources de financement</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                            {repartitionSource.map((s: any) => (
                                <div key={s.code} className="rounded-md border bg-muted/30 p-3">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium truncate">{s.source}</p>
                                            <p className="text-[10px] uppercase tracking-wide text-muted-foreground">{s.type}</p>
                                        </div>
                                        <p className="text-sm font-semibold tabular-nums">{formatCurrency(s.total)}</p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline"><Link href="/budget/lignes">Voir toutes les lignes</Link></Button>
                    <Button asChild variant="outline"><Link href="/budget/imports">Imports</Link></Button>
                    <Button asChild variant="outline"><Link href={`/budget/exports?exercice_id=${exercice.id}&template=consolide`}>Exporter consolidé</Link></Button>
                    <Button asChild variant="outline"><Link href={`/budget/exports?exercice_id=${exercice.id}&template=comparatif`}>Export comparatif</Link></Button>
                </div>
            </div>
        </AppLayout>
    );
}

function KpiCard({ title, value, sub, icon: Icon, bg }: any) {
    return (
        <Card>
            <CardContent className="flex items-center gap-4 p-5">
                <div className={`flex h-12 w-12 items-center justify-center rounded-xl ${bg}`}><Icon className="h-6 w-6" /></div>
                <div className="flex-1 min-w-0">
                    <p className="text-xs font-medium text-muted-foreground">{title}</p>
                    <p className="truncate text-xl font-bold tabular-nums">{value}</p>
                    <p className="truncate text-xs text-muted-foreground">{sub}</p>
                </div>
            </CardContent>
        </Card>
    );
}

function Balance({ label, value, icon: Icon, color }: any) {
    return (
        <div className="flex items-center justify-between gap-3">
            <div className="flex items-center gap-2 min-w-0">
                <Icon className={`h-4 w-4 shrink-0 ${color}`} />
                <span className="truncate text-xs">{label}</span>
            </div>
            <span className="tabular-nums font-medium text-sm">{formatCurrency(value)}</span>
        </div>
    );
}
