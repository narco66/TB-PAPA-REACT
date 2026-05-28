import { Link } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    Award,
    BarChart3,
    Bell,
    Boxes,
    Building2,
    Calendar,
    CheckCircle2,
    ClipboardCheck,
    Clock,
    Diamond,
    FileText,
    GanttChartSquare,
    Gauge,
    KeyRound,
    LineChart as LineChartIcon,
    ListChecks,
    Package,
    PiggyBank,
    Plus,
    ShieldCheck,
    Target,
    TrendingUp,
    Users,
} from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { AppLayout } from '@/components/layout/app-layout';
import { StatCard } from '@/components/dashboard/stat-card';
import { PdfQuickMenu } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { formatCurrency, formatPercent } from '@/lib/utils';

interface Stats {
    papa_actif: {
        id: number;
        annee: number;
        libelle: string;
        statut: string;
        taux_execution_physique: number;
        taux_execution_financier: number;
        progression_temporelle: number;
        date_debut: string | null;
        date_fin: string | null;
    } | null;
    rbm: Record<string, number>;
    execution: { taux_global: number; taux_axes_moyen: number; taux_produits_moyen: number };
    activites: Record<string, number>;
    taches: Record<string, number>;
    budget: Record<string, number>;
    alertes: { ouvertes: number; critiques: number; attention: number; info: number; resolues_30j: number; recentes: Array<{ id: number; niveau: string; titre: string; date: string }> };
    validations: { en_attente: number; approuvees_30j: number; rejetees_30j: number };
    indicateurs: { total: number; atteints: number; a_risque: number; taux_moyen: number };
    documents: { rapports_30j: number; telechargements_total: number; recents: Array<{ id: number; titre: string; categorie: string; date: string; auteur: string }> };
    gouvernance: { utilisateurs_actifs: number; departements: number; mfa_activee: number };
    top: {
        axes_performants: Array<{ id: number; code: string; libelle: string; taux: number; departement: string }>;
        activites_critiques: Array<{ id: number; code: string; libelle: string; date_fin: string; jours_retard: number; taux: number; axe: string }>;
        echeances_proches: Array<{ id: number; code: string; libelle: string; date_fin: string; jours_restants: number; taux: number }>;
    };
    departements: Array<{ id: number; code: string; libelle: string; nombre_axes: number; taux_moyen: number }>;
    evolution: Array<{ mois: string; realisees: number }>;
    repartition: Array<{ statut: string; count: number; color: string }>;
    feed_audit: Array<{ id: number; description: string; event: string; subject: string; auteur: string; date: string }>;
}

interface Props {
    stats: Stats;
    niveau_utilisateur: number;
    roles: string[];
    can: { papa_create: boolean; view_audit: boolean; view_budget: boolean };
}

const asArray = <T,>(value: T[] | Record<string, T> | null | undefined): T[] => {
    if (Array.isArray(value)) {
        return value;
    }

    if (value && typeof value === 'object') {
        return Object.values(value);
    }

    return [];
};

const NIVEAU_BADGE: Record<string, string> = {
    critique: 'bg-rose-50 text-rose-700 border-rose-200',
    attention: 'bg-amber-50 text-amber-700 border-amber-200',
    info: 'bg-sky-50 text-sky-700 border-sky-200',
};

export default function Dashboard({ stats, can }: Props) {
    const p = stats.papa_actif;
    const papaId = p?.id;

    // Defensive defaults — protègent contre un cache stale ou une réponse partielle
    const evolution = asArray(stats.evolution);
    const repartition = asArray(stats.repartition);
    const departements = asArray(stats.departements);
    const topAxes = asArray(stats.top?.axes_performants);
    const topCritiques = asArray(stats.top?.activites_critiques);
    const topEcheances = asArray(stats.top?.echeances_proches);
    const alertesRecentes = asArray(stats.alertes?.recentes);
    const documentsRecents = asArray(stats.documents?.recents);
    const feedAudit = asArray(stats.feed_audit);
    const rbm = stats.rbm ?? { axes: 0, produits: 0, sous_produits: 0, activites: 0, taches: 0, indicateurs: 0 };
    const execution = stats.execution ?? { taux_global: 0, taux_axes_moyen: 0, taux_produits_moyen: 0 };
    const activites = stats.activites ?? { total: 0, en_retard: 0 };
    const alertes = stats.alertes ?? { critiques: 0, ouvertes: 0, attention: 0, info: 0, resolues_30j: 0, recentes: [] };
    const validations = stats.validations ?? { en_attente: 0, approuvees_30j: 0, rejetees_30j: 0 };
    const indicateurs = stats.indicateurs ?? { total: 0, atteints: 0, a_risque: 0, taux_moyen: 0 };
    const documents = stats.documents ?? { rapports_30j: 0, telechargements_total: 0, recents: [] };
    const gouvernance = stats.gouvernance ?? { utilisateurs_actifs: 0, departements: 0, mfa_activee: 0 };
    const budget = stats.budget ?? {
        total_prevu: 0, total_engage: 0, total_paye: 0, disponible: 0,
        taux_engagement: 0, taux_consommation: 0, ceeac_em: 0, ptf: 0,
        ratio_autonomie: 0, total_recettes: 0, nombre_lignes: 0,
    };

    return (
        <AppLayout
            pageTitle="Centre de pilotage exécutif"
            actions={
                <PdfQuickMenu
                    reports={[
                        { reportKey: 'tableau_bord_executif', label: 'Tableau de bord exécutif', params: papaId ? { papa_id: papaId } : {}, description: 'Snapshot complet pour Présidence/SG' },
                        ...(papaId
                            ? [
                                  { reportKey: 'synthese_executive', label: 'Synthèse exécutive', params: { papa_id: papaId }, description: 'Cabinet Présidentiel' },
                                  { reportKey: 'papa_strategique', label: 'Plan stratégique PAPA', params: { papa_id: papaId }, description: 'Vue intégrale du PAPA actif' },
                              ]
                            : []),
                    ]}
                />
            }
        >
            <div className="space-y-6">
                {/* ===== Hero institutionnel ===== */}
                <Card className="overflow-hidden border-primary/30">
                    <div className="relative bg-linear-to-br from-ceeac-blue via-ceeac-blue/90 to-ceeac-blue/70 px-6 py-8 text-white">
                        <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 20% 20%, white 1px, transparent 1px)', backgroundSize: '24px 24px' }} />
                        <div className="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                            <div className="flex items-center gap-5">
                                <img src="/images/LOGO-CEEAC.jpg" alt="CEEAC" className="h-20 w-20 rounded-xl bg-white p-1 object-contain shadow-lg" />
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Commission de la CEEAC</p>
                                    <h1 className="text-2xl font-bold leading-tight md:text-3xl">Centre de pilotage exécutif</h1>
                                    <p className="mt-1 text-sm text-white/80">Plan d'Actions Prioritaires Annuel · Suivi-évaluation RBM/GAR</p>
                                </div>
                            </div>
                            {p ? (
                                <div className="grid grid-cols-3 gap-4 rounded-xl bg-white/10 p-4 backdrop-blur-sm md:gap-6">
                                    <HeroMetric label="Exécution physique" value={`${formatPercent(p.taux_execution_physique)}`} />
                                    <HeroMetric label="Exécution financière" value={`${formatPercent(p.taux_execution_financier)}`} />
                                    <HeroMetric label="Calendrier" value={`${p.progression_temporelle.toFixed(0)}%`} sub="écoulé" />
                                </div>
                            ) : (
                                <div className="rounded-xl bg-white/10 p-4 text-center backdrop-blur-sm">
                                    {can.papa_create ? (
                                        <Button asChild variant="secondary">
                                            <Link href="/papa/create"><Plus className="h-4 w-4" />Initier le premier PAPA</Link>
                                        </Button>
                                    ) : (
                                        <p className="text-sm">Aucun PAPA actif.</p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                    {p && (
                        <CardContent className="flex flex-col gap-3 border-t bg-muted/30 p-4 md:flex-row md:items-center md:justify-between">
                            <div className="flex items-center gap-3">
                                <RbmStatusBadge statut={p.statut} />
                                <Link href={`/papa/${p.id}`} className="font-semibold hover:underline">
                                    {p.libelle}
                                </Link>
                                <span className="text-xs text-muted-foreground">Exercice {p.annee}</span>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <Calendar className="h-3.5 w-3.5" />
                                {p.date_debut && p.date_fin && (
                                    <span>
                                        {new Date(p.date_debut).toLocaleDateString('fr-FR')} → {new Date(p.date_fin).toLocaleDateString('fr-FR')}
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    )}
                </Card>

                {/* ===== KPI synthétiques ===== */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Taux d'exécution global" value={formatPercent(execution.taux_global)} sub={`Moyenne ${rbm.axes} axes`} icon={Gauge} iconBg="bg-emerald-100 text-emerald-700" accent="success" />
                    <StatCard title="Activités en retard" value={activites.en_retard} sub={`sur ${activites.total} activités`} icon={Clock} iconBg={activites.en_retard > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'} accent={activites.en_retard > 0 ? 'destructive' : 'success'} href="/activites?retard=1" />
                    <StatCard title="Alertes critiques" value={alertes.critiques} sub={`${alertes.ouvertes} ouvertes au total`} icon={AlertTriangle} iconBg={alertes.critiques > 0 ? 'bg-rose-100 text-rose-700' : 'bg-muted text-muted-foreground'} accent={alertes.critiques > 0 ? 'destructive' : 'default'} href="/alertes" />
                    <StatCard title="Validations en attente" value={validations.en_attente} sub={`${validations.approuvees_30j} approuvées (30j)`} icon={ClipboardCheck} iconBg="bg-amber-100 text-amber-700" accent="warning" />
                </div>

                {/* ===== Chaîne RBM 5 niveaux ===== */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Target className="h-5 w-5" />Chaîne RBM/GAR officielle CEEAC</CardTitle>
                        <CardDescription>Axe → Produit → Sous-Produit → Activité → Tâche</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                            <RbmCounter title="Axes" count={rbm.axes} icon={Target} href="/rbm/axes" color="bg-blue-50 text-blue-700 border-blue-200" />
                            <RbmCounter title="Produits" count={rbm.produits} icon={Package} href="/rbm/produits" color="bg-indigo-50 text-indigo-700 border-indigo-200" />
                            <RbmCounter title="Sous-Produits" count={rbm.sous_produits} icon={Boxes} href="/rbm/sous-produits" color="bg-violet-50 text-violet-700 border-violet-200" />
                            <RbmCounter title="Activités" count={rbm.activites} icon={GanttChartSquare} href="/activites" color="bg-emerald-50 text-emerald-700 border-emerald-200" />
                            <RbmCounter title="Tâches" count={rbm.taches} icon={ListChecks} href="/rbm/taches" color="bg-amber-50 text-amber-700 border-amber-200" />
                            <RbmCounter title="Indicateurs" count={rbm.indicateurs} icon={BarChart3} href="/indicateurs" color="bg-rose-50 text-rose-700 border-rose-200" />
                        </div>
                    </CardContent>
                </Card>

                {/* ===== Graphiques : évolution + répartition ===== */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><LineChartIcon className="h-5 w-5" />Évolution des activités réalisées</CardTitle>
                            <CardDescription>Cumul mensuel sur le PAPA actif</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {evolution.length > 0 ? (
                                <ResponsiveContainer width="100%" height={260}>
                                    <LineChart data={evolution}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis dataKey="mois" tick={{ fontSize: 11 }} stroke="currentColor" className="text-muted-foreground" />
                                        <YAxis tick={{ fontSize: 11 }} stroke="currentColor" className="text-muted-foreground" />
                                        <Tooltip contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))', borderRadius: 8 }} />
                                        <Line type="monotone" dataKey="realisees" name="Activités réalisées" stroke="hsl(var(--primary))" strokeWidth={2} dot={{ r: 3 }} activeDot={{ r: 5 }} />
                                    </LineChart>
                                </ResponsiveContainer>
                            ) : (
                                <EmptyChart label="Aucune donnée d'exécution sur la période" />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><Activity className="h-5 w-5" />Répartition des activités</CardTitle>
                            <CardDescription>Par statut</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {repartition.some((r) => r.count > 0) ? (
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart>
                                        <Pie data={repartition.filter((r) => r.count > 0)} dataKey="count" nameKey="statut" cx="50%" cy="50%" innerRadius={50} outerRadius={90} paddingAngle={2}>
                                            {repartition.map((entry) => (
                                                <Cell key={entry.statut} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))', borderRadius: 8 }} />
                                        <Legend wrapperStyle={{ fontSize: 12 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            ) : (
                                <EmptyChart label="Aucune activité" />
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ===== Budget & financement ===== */}
                {can.view_budget && (
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2"><PiggyBank className="h-5 w-5" />Exécution budgétaire</CardTitle>
                                <CardDescription>
                                    {budget.nombre_lignes > 0 ? `${budget.nombre_lignes} lignes budgétaires` : 'Aucune ligne budgétaire pour cet exercice'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-3 gap-4">
                                    <BudgetMetric label="Budget prévu" value={budget.total_prevu} highlight />
                                    <BudgetMetric label="Engagé" value={budget.total_engage} percent={budget.taux_engagement} />
                                    <BudgetMetric label="Payé" value={budget.total_paye} percent={budget.taux_consommation} />
                                </div>
                                <div className="space-y-2 border-t pt-4">
                                    <div className="flex justify-between text-sm">
                                        <span>Taux d'engagement</span>
                                        <span className="tabular-nums font-medium">{formatPercent(budget.taux_engagement)}</span>
                                    </div>
                                    <Progress value={budget.taux_engagement} indicatorClassName="bg-amber-500" />
                                    <div className="flex justify-between text-sm">
                                        <span>Taux de consommation</span>
                                        <span className="tabular-nums font-medium">{formatPercent(budget.taux_consommation)}</span>
                                    </div>
                                    <Progress value={budget.taux_consommation} indicatorClassName="bg-emerald-500" />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2"><Building2 className="h-5 w-5" />Sources de financement</CardTitle>
                                <CardDescription>CEEAC-EM vs Partenaires</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <SourceBar label="CEEAC-EM" value={budget.ceeac_em} total={budget.ceeac_em + budget.ptf} color="bg-ceeac-blue" />
                                <SourceBar label="Partenaires (PTF)" value={budget.ptf} total={budget.ceeac_em + budget.ptf} color="bg-ceeac-gold" />
                                <div className="mt-3 border-t pt-3 text-center">
                                    <p className="text-xs text-muted-foreground">Taux d'autonomie financière</p>
                                    <p className="text-2xl font-bold tabular-nums">{formatPercent(budget.ratio_autonomie)}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* ===== Top axes performants + Activités critiques + Échéances ===== */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><Award className="h-5 w-5" />Top axes performants</CardTitle>
                            <CardDescription>Classement par taux d'exécution</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {topAxes.length === 0 ? (
                                <EmptyLine label="Aucun axe enregistré" />
                            ) : (
                                topAxes.map((a) => (
                                    <div key={a.id} className="space-y-1.5">
                                        <div className="flex items-center justify-between gap-2">
                                            <Link href={`/rbm/axes/${a.id}`} className="text-sm font-medium hover:underline flex-1 min-w-0 truncate">
                                                <span className="font-mono text-xs text-muted-foreground mr-2">{a.code}</span>
                                                {a.libelle}
                                            </Link>
                                            <Badge variant="outline" className="tabular-nums shrink-0">{a.taux}%</Badge>
                                        </div>
                                        <Progress value={a.taux} indicatorClassName={a.taux >= 75 ? 'bg-emerald-500' : a.taux >= 40 ? 'bg-amber-500' : 'bg-rose-500'} />
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-rose-200/60">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-rose-700"><AlertTriangle className="h-5 w-5" />Activités critiques</CardTitle>
                            <CardDescription>En retard, par ancienneté du retard</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {topCritiques.length === 0 ? (
                                <EmptyLine label="Aucune activité en retard ✓" />
                            ) : (
                                topCritiques.map((a) => (
                                    <Link key={a.id} href={`/activites/${a.id}`} className="block rounded-md border border-rose-200/60 p-2 hover:bg-rose-50/50">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="text-sm font-medium truncate flex-1 min-w-0">
                                                <span className="font-mono text-xs text-muted-foreground mr-1">{a.code}</span>
                                                {a.libelle}
                                            </p>
                                            <Badge variant="destructive" className="shrink-0 text-xs">J+{a.jours_retard}</Badge>
                                        </div>
                                        <p className="text-xs text-muted-foreground mt-0.5">
                                            {a.axe} · {a.taux}% exécuté · échéance {new Date(a.date_fin).toLocaleDateString('fr-FR')}
                                        </p>
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card className="border-amber-200/60">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-amber-700"><Calendar className="h-5 w-5" />Échéances à 30 jours</CardTitle>
                            <CardDescription>Activités à finaliser sous 30 j</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {topEcheances.length === 0 ? (
                                <EmptyLine label="Aucune échéance proche" />
                            ) : (
                                topEcheances.map((a) => (
                                    <Link key={a.id} href={`/activites/${a.id}`} className="block rounded-md border border-amber-200/60 p-2 hover:bg-amber-50/50">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="text-sm font-medium truncate flex-1 min-w-0">
                                                <span className="font-mono text-xs text-muted-foreground mr-1">{a.code}</span>
                                                {a.libelle}
                                            </p>
                                            <Badge variant="warning" className="shrink-0 text-xs">J-{a.jours_restants}</Badge>
                                        </div>
                                        <p className="text-xs text-muted-foreground mt-0.5">
                                            {a.taux}% · échéance {new Date(a.date_fin).toLocaleDateString('fr-FR')}
                                        </p>
                                    </Link>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ===== Performance par département (bar chart) ===== */}
                {departements.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><Building2 className="h-5 w-5" />Performance par département</CardTitle>
                            <CardDescription>Taux moyen d'exécution des axes rattachés</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={260}>
                                <BarChart data={departements} layout="horizontal" margin={{ top: 5, right: 20, bottom: 5, left: 5 }}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="code" tick={{ fontSize: 11 }} stroke="currentColor" className="text-muted-foreground" />
                                    <YAxis tick={{ fontSize: 11 }} stroke="currentColor" className="text-muted-foreground" domain={[0, 100]} />
                                    <Tooltip contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))', borderRadius: 8 }} formatter={(value) => `${Number(value ?? 0)}%`} />
                                    <Bar dataKey="taux_moyen" name="Taux moyen" radius={[6, 6, 0, 0]}>
                                        {departements.map((d, i) => (
                                            <Cell key={i} fill={d.taux_moyen >= 75 ? '#10b981' : d.taux_moyen >= 40 ? '#f59e0b' : '#ef4444'} />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                )}

                {/* ===== Indicateurs CMR + Gouvernance ===== */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Indicateurs atteints" value={`${indicateurs.atteints} / ${indicateurs.total}`} sub={`Moyenne ${indicateurs.taux_moyen}%`} icon={CheckCircle2} iconBg="bg-emerald-100 text-emerald-700" />
                    <StatCard title="Indicateurs à risque" value={indicateurs.a_risque} sub="< 40% d'atteinte" icon={Diamond} iconBg="bg-rose-100 text-rose-700" accent={indicateurs.a_risque > 0 ? 'destructive' : 'default'} />
                    <StatCard title="Utilisateurs actifs" value={gouvernance.utilisateurs_actifs} sub={`${gouvernance.departements} départements`} icon={Users} iconBg="bg-blue-100 text-blue-700" />
                    <StatCard title="Comptes 2FA activée" value={gouvernance.mfa_activee} sub={`sur ${gouvernance.utilisateurs_actifs} actifs`} icon={ShieldCheck} iconBg="bg-violet-100 text-violet-700" />
                </div>

                {/* ===== Alertes récentes + Rapports récents ===== */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2"><Bell className="h-5 w-5" />Alertes récentes</CardTitle>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/alertes">Tout voir</Link>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {alertesRecentes.length === 0 ? (
                                <EmptyLine label="Aucune alerte ouverte" />
                            ) : (
                                <ul className="space-y-2">
                                    {alertesRecentes.map((a) => (
                                        <li key={a.id} className={`flex items-start gap-2 rounded-md border p-2 ${NIVEAU_BADGE[a.niveau] ?? ''}`}>
                                            <AlertTriangle className="h-4 w-4 mt-0.5 shrink-0" />
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium truncate">{a.titre}</p>
                                                <p className="text-xs opacity-70">{a.date}</p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2"><FileText className="h-5 w-5" />Rapports récents</CardTitle>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/rapports/historique">Historique</Link>
                                </Button>
                            </div>
                            <CardDescription>{documents.rapports_30j} générés (30j) · {documents.telechargements_total} téléchargements totaux</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {documentsRecents.length === 0 ? (
                                <EmptyLine label="Aucun rapport généré" />
                            ) : (
                                <ul className="space-y-2">
                                    {documentsRecents.map((r) => (
                                        <li key={r.id} className="flex items-start gap-2 rounded-md border p-2 hover:bg-accent/50">
                                            <FileText className="h-4 w-4 mt-0.5 shrink-0 text-muted-foreground" />
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium truncate">{r.titre}</p>
                                                <p className="text-xs text-muted-foreground">{r.categorie} · {r.auteur ?? 'Système'} · {r.date}</p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* ===== Audit feed ===== */}
                {can.view_audit && feedAudit.length > 0 && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle className="flex items-center gap-2"><KeyRound className="h-5 w-5" />Activité institutionnelle récente</CardTitle>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/admin/audit">Journal complet</Link>
                                </Button>
                            </div>
                            <CardDescription>Dernières opérations tracées (Spatie Activity Log)</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul className="divide-y">
                                {feedAudit.map((a) => (
                                    <li key={a.id} className="flex items-center justify-between gap-2 py-2 text-sm">
                                        <div className="flex items-center gap-2 min-w-0 flex-1">
                                            <Badge variant="outline" className="text-[10px] shrink-0">{a.event}</Badge>
                                            <span className="font-mono text-xs text-muted-foreground shrink-0">{a.subject}</span>
                                            <span className="truncate">{a.description}</span>
                                        </div>
                                        <span className="text-xs text-muted-foreground shrink-0">
                                            {a.auteur} · {a.date}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}

function HeroMetric({ label, value, sub }: { label: string; value: string; sub?: string }) {
    return (
        <div className="text-center">
            <p className="text-[10px] uppercase tracking-wider text-white/70">{label}</p>
            <p className="text-2xl font-bold tabular-nums leading-tight">{value}</p>
            {sub && <p className="text-[10px] text-white/60">{sub}</p>}
        </div>
    );
}

function RbmCounter({ title, count, icon: Icon, href, color }: { title: string; count: number; icon: any; href: string; color: string }) {
    return (
        <Link href={href}>
            <div className={`flex items-center gap-3 rounded-xl border p-4 transition-shadow hover:shadow-md ${color}`}>
                <Icon className="h-6 w-6 shrink-0" />
                <div className="min-w-0">
                    <p className="text-2xl font-bold tabular-nums leading-none">{count}</p>
                    <p className="text-xs mt-1 opacity-80">{title}</p>
                </div>
            </div>
        </Link>
    );
}

function BudgetMetric({ label, value, percent, highlight }: { label: string; value: number; percent?: number; highlight?: boolean }) {
    return (
        <div className={`rounded-md border p-3 ${highlight ? 'bg-primary/5 border-primary/30' : 'bg-muted/30'}`}>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-base font-bold tabular-nums truncate">{formatCurrency(value)}</p>
            {percent !== undefined && <p className="text-xs text-muted-foreground tabular-nums">{percent}%</p>}
        </div>
    );
}

function SourceBar({ label, value, total, color }: { label: string; value: number; total: number; color: string }) {
    const pct = total > 0 ? (value / total) * 100 : 0;
    return (
        <div className="space-y-1.5">
            <div className="flex items-center justify-between text-sm">
                <span>{label}</span>
                <span className="tabular-nums font-medium">{formatCurrency(value)}</span>
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                <div className={`h-full ${color} transition-all`} style={{ width: `${pct}%` }} />
            </div>
            <p className="text-right text-[10px] text-muted-foreground tabular-nums">{pct.toFixed(1)}%</p>
        </div>
    );
}

function EmptyChart({ label }: { label: string }) {
    return (
        <div className="flex h-[260px] items-center justify-center text-sm text-muted-foreground">
            <span>{label}</span>
        </div>
    );
}

function EmptyLine({ label }: { label: string }) {
    return <p className="text-sm text-muted-foreground py-4 text-center">{label}</p>;
}
