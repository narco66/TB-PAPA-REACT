import { Link, router } from '@inertiajs/react';
import {
    AlertTriangle, CheckCircle2, ClipboardCheck, Clock, Coins,
    CreditCard, FileBarChart, FileWarning, ListChecks, Receipt, Wallet,
} from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Cell, Legend, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { AppLayout } from '@/components/layout/app-layout';
import { ExcelExportButton } from '@/components/common/excel-export-button';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const COLORS = {
    engagement: '#1e5cb3',
    liquidation: '#16a34a',
    ordonnancement: '#f59e0b',
    paiement: '#dc2626',
    brouillon: '#94a3b8',
    soumis: '#3b82f6',
    en_validation_hierarchique: '#0ea5e9',
    retourne_correction: '#f59e0b',
    rejete: '#dc2626',
    valide: '#16a34a',
    engage: '#059669',
    annule: '#6b7280',
};

const fmt = (n: number) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Number(n || 0));
const pct = (n: number) => `${Number(n || 0).toFixed(1)}%`;

export default function ExpenseDashboard({ stats }: any) {
    const exercice = stats.exercice;
    const exercices = stats.exercices_disponibles ?? [];
    const kpis = stats.kpis ?? {};
    const cycle = stats.cycle_ipsas ?? {};
    const expressions = stats.expressions ?? {};
    const parType = stats.par_type_engagement ?? [];
    const parDep = stats.par_departement ?? [];
    const topF = stats.top_fournisseurs ?? [];
    const enAttente = stats.en_attente_validation ?? [];
    const evolution = stats.evolution_mensuelle ?? [];
    const sfPending = stats.service_fait_pending ?? [];
    const recPending = stats.receptions_pending ?? [];

    const expressionsChart = Object.entries(expressions).map(([k, v]) => ({
        name: k,
        value: Number(v),
        fill: (COLORS as any)[k] ?? '#64748b',
    })).filter((d) => d.value > 0);

    const cycleChart = Object.entries(cycle).map(([k, v]: [string, any]) => ({
        name: k,
        nb: Number(v.nb),
        montant: Number(v.montant),
        fill: (COLORS as any)[k] ?? '#64748b',
    }));

    const changeExercice = (id: string) => router.get('/expense', { exercice_id: id || undefined }, { preserveScroll: true });

    return (
        <AppLayout
            pageTitle="Tableau de bord — Chaîne de la dépense"
            breadcrumbs={[{ label: 'Chaîne de la dépense', href: '/expense' }, { label: 'Tableau de bord' }]}
            actions={
                <>
                    <ExcelExportButton journal="engagements" filtres={{ exercice_id: exercice?.id }} label="Engagements" />
                    <ExcelExportButton journal="paiements" filtres={{ exercice_id: exercice?.id }} label="Paiements" />
                    <PdfExportButton reportKey="tableau_bord_expense" filtres={{ exercice_id: exercice?.id }} />
                </>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow={`Exercice ${exercice?.annee ?? '—'} · ${exercice?.statut ?? ''}`}
                    title="Pilotage de la chaîne de la dépense"
                    description="Expression du besoin → engagement → liquidation → ordonnancement → paiement. Conformité RGCP, IPSAS 1 & 24, COSO ERM."
                    metrics={[
                        { icon: Receipt, label: 'Expressions', value: String(kpis.expressions_total ?? 0) },
                        { icon: Coins, label: 'Engagé', value: fmt(kpis.montant_engage ?? 0) },
                        { icon: CreditCard, label: 'Payé', value: fmt(kpis.montant_paye ?? 0) },
                        { icon: AlertTriangle, label: 'Retards', value: String(kpis.engagements_en_retard ?? 0) },
                    ]}
                    footer={`Taux de paiement ${pct(kpis.taux_paiement ?? 0)} · Reste à payer ${fmt(kpis.reste_a_payer ?? 0)}`}
                />

                <Card>
                    <CardContent className="p-4 flex items-center gap-3">
                        <span className="text-sm font-medium">Exercice :</span>
                        <select className="rounded-md border px-3 py-2 text-sm"
                            value={exercice?.id ?? ''}
                            onChange={(e) => changeExercice(e.target.value)}>
                            <option value="">Tous</option>
                            {exercices.map((e: any) => <option key={e.id} value={e.id}>{e.annee} — {e.libelle}</option>)}
                        </select>
                        <div className="ml-auto flex gap-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/expense/requests"><Receipt className="h-4 w-4" />Expressions</Link>
                            </Button>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/expense/service-fait"><ClipboardCheck className="h-4 w-4" />Service fait</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* === KPI Cards === */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <KpiCard icon={Receipt} label="En validation" value={kpis.expressions_en_validation ?? 0} accent="info" />
                    <KpiCard icon={CheckCircle2} label="Validées" value={kpis.expressions_validees ?? 0} accent="success" />
                    <KpiCard icon={Coins} label="Engagées" value={kpis.expressions_engagees ?? 0} accent="primary" />
                    <KpiCard icon={FileWarning} label="Rejetées" value={kpis.expressions_rejetees ?? 0} accent="danger" />
                    <KpiCard icon={ClipboardCheck} label="SF en attente" value={kpis.service_fait_en_attente ?? 0} accent="warning" />
                    <KpiCard icon={Wallet} label="Réceptions" value={kpis.receptions_en_attente ?? 0} accent="warning" />
                </div>

                {/* === Cycle IPSAS === */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><FileBarChart className="h-5 w-5" />Cycle IPSAS</CardTitle>
                        <CardDescription>Engagement → Liquidation → Ordonnancement → Paiement</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                            {Object.entries(cycle).map(([k, v]: [string, any]) => (
                                <div key={k} className="rounded-xl border bg-card p-4">
                                    <div className="text-xs uppercase text-muted-foreground font-semibold tracking-wider">{k}</div>
                                    <div className="text-2xl font-bold tabular-nums mt-2" style={{ color: (COLORS as any)[k] }}>{v.nb}</div>
                                    <div className="text-xs text-muted-foreground tabular-nums">{fmt(v.montant)}</div>
                                </div>
                            ))}
                        </div>
                        {cycleChart.some((c) => c.montant > 0) && (
                            <ResponsiveContainer width="100%" height={220}>
                                <BarChart data={cycleChart}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                                    <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => fmt(v)} />
                                    <Tooltip formatter={(v: number) => fmt(v)} contentStyle={{ background: 'hsl(var(--background))', border: '1px solid hsl(var(--border))', borderRadius: 8 }} />
                                    <Bar dataKey="montant" name="Montant">
                                        {cycleChart.map((entry, idx) => <Cell key={idx} fill={entry.fill} />)}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </CardContent>
                </Card>

                {/* === Expressions par statut + Évolution === */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Expressions par statut</CardTitle>
                            <CardDescription>Volumétrie globale</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {expressionsChart.length === 0 ? (
                                <EmptyChart label="Aucune expression" />
                            ) : (
                                <ResponsiveContainer width="100%" height={260}>
                                    <PieChart>
                                        <Pie data={expressionsChart} dataKey="value" nameKey="name" cx="50%" cy="50%" innerRadius={50} outerRadius={90} paddingAngle={2}>
                                            {expressionsChart.map((entry, i) => <Cell key={i} fill={entry.fill} />)}
                                        </Pie>
                                        <Tooltip />
                                        <Legend wrapperStyle={{ fontSize: 11 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Évolution mensuelle</CardTitle>
                            <CardDescription>Engagements vs Paiements cumulés</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {evolution.length === 0 ? (
                                <EmptyChart label="Aucune donnée" />
                            ) : (
                                <ResponsiveContainer width="100%" height={260}>
                                    <LineChart data={evolution}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis dataKey="mois" tick={{ fontSize: 11 }} />
                                        <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => fmt(v)} />
                                        <Tooltip formatter={(v: number) => fmt(v)} />
                                        <Legend wrapperStyle={{ fontSize: 11 }} />
                                        <Line type="monotone" dataKey="engagements" name="Engagements" stroke={COLORS.engagement} strokeWidth={2} dot={{ r: 3 }} />
                                        <Line type="monotone" dataKey="paiements" name="Paiements" stroke={COLORS.paiement} strokeWidth={2} dot={{ r: 3 }} />
                                    </LineChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* === Par type d'engagement === */}
                {parType.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Engagements par type</CardTitle>
                            <CardDescription>18 types métiers — Top par montant</CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead className="text-right">Nombre</TableHead>
                                        <TableHead className="text-right">Montant estimé</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {parType.map((t: any) => (
                                        <TableRow key={t.type}>
                                            <TableCell className="font-medium">{t.label}</TableCell>
                                            <TableCell className="text-right tabular-nums">{t.nb}</TableCell>
                                            <TableCell className="text-right tabular-nums">{fmt(t.montant)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* === Par département + Top fournisseurs === */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {parDep.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Top départements</CardTitle>
                                <CardDescription>Engagements par département (montant)</CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Code</TableHead>
                                            <TableHead>Libellé</TableHead>
                                            <TableHead className="text-right">Nb</TableHead>
                                            <TableHead className="text-right">Montant</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {parDep.map((d: any) => (
                                            <TableRow key={d.code}>
                                                <TableCell className="font-mono text-xs">{d.code}</TableCell>
                                                <TableCell className="text-sm">{d.libelle}</TableCell>
                                                <TableCell className="text-right tabular-nums">{d.nb}</TableCell>
                                                <TableCell className="text-right tabular-nums text-sm">{fmt(d.montant)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    )}

                    {topF.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Top fournisseurs</CardTitle>
                                <CardDescription>Engagements par fournisseur</CardDescription>
                            </CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Code</TableHead>
                                            <TableHead>Libellé</TableHead>
                                            <TableHead className="text-right">Nb</TableHead>
                                            <TableHead className="text-right">Montant</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {topF.map((f: any) => (
                                            <TableRow key={f.code}>
                                                <TableCell className="font-mono text-xs">{f.code}</TableCell>
                                                <TableCell className="text-sm">{f.libelle}</TableCell>
                                                <TableCell className="text-right tabular-nums">{f.nb_engagements}</TableCell>
                                                <TableCell className="text-right tabular-nums text-sm">{fmt(f.montant)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* === Files d'attente === */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {enAttente.length > 0 && (
                        <Card className="border-amber-200/60">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-amber-700">
                                    <Clock className="h-5 w-5" />Validation en attente
                                </CardTitle>
                                <CardDescription>Top 8 par ancienneté</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {enAttente.map((r: any) => (
                                    <Link key={r.id} href={`/expense/requests/${r.id}`} className="block border rounded-md p-3 hover:bg-accent text-sm transition-colors">
                                        <div className="flex justify-between items-start">
                                            <div className="font-mono text-xs">{r.numero}</div>
                                            <div className="text-xs text-amber-700 font-medium">{r.depuis_jours} j</div>
                                        </div>
                                        <div className="font-medium mt-1 line-clamp-1">{r.objet}</div>
                                        <div className="text-xs text-muted-foreground">{r.demandeur} · {fmt(r.montant)} {r.devise}</div>
                                    </Link>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {sfPending.length > 0 && (
                        <Card className="border-blue-200/60">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-blue-700">
                                    <ClipboardCheck className="h-5 w-5" />Service fait à valider
                                </CardTitle>
                                <CardDescription>Top 5 projets</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {sfPending.map((c: any) => (
                                    <div key={c.reference} className="border rounded-md p-3 text-sm">
                                        <div className="flex justify-between">
                                            <div className="font-mono text-xs">{c.reference}</div>
                                            <div className="text-xs text-muted-foreground">{c.date}</div>
                                        </div>
                                        <div className="text-xs text-muted-foreground mt-1">Eng. {c.engagement} · {fmt(c.montant)}</div>
                                        <div className="text-xs text-muted-foreground">{c.constate_par}</div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {recPending.length > 0 && (
                        <Card className="border-cyan-200/60">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-cyan-700">
                                    <ListChecks className="h-5 w-5" />Réceptions à valider
                                </CardTitle>
                                <CardDescription>Top 5 projets</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {recPending.map((r: any) => (
                                    <div key={r.reference} className="border rounded-md p-3 text-sm">
                                        <div className="flex justify-between">
                                            <div className="font-mono text-xs">{r.reference}</div>
                                            <div className="text-xs text-muted-foreground">{r.date}</div>
                                        </div>
                                        <div className="text-xs text-muted-foreground mt-1">{r.nature} · {r.type} · {r.conformite}</div>
                                        <div className="text-xs text-muted-foreground">{fmt(r.montant)}</div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

function KpiCard({ icon: Icon, label, value, accent = 'default' }: any) {
    const colors: Record<string, string> = {
        success: 'text-emerald-700 bg-emerald-100',
        danger: 'text-rose-700 bg-rose-100',
        warning: 'text-amber-700 bg-amber-100',
        info: 'text-blue-700 bg-blue-100',
        primary: 'text-indigo-700 bg-indigo-100',
        default: 'text-muted-foreground bg-muted',
    };
    return (
        <Card>
            <CardContent className="p-4">
                <div className={`inline-flex h-10 w-10 items-center justify-center rounded-lg ${colors[accent]} mb-2`}>
                    <Icon className="h-5 w-5" />
                </div>
                <div className="text-2xl font-bold tabular-nums">{value}</div>
                <div className="text-xs text-muted-foreground mt-1">{label}</div>
            </CardContent>
        </Card>
    );
}

function EmptyChart({ label }: { label: string }) {
    return (
        <div className="h-[260px] flex flex-col items-center justify-center text-muted-foreground text-sm">
            <FileBarChart className="h-8 w-8 mb-2 opacity-30" />
            {label}
        </div>
    );
}
