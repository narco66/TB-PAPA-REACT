import { Link, router } from '@inertiajs/react';
import {
    Archive,
    BookOpen,
    Calendar,
    CheckCircle2,
    ClipboardCheck,
    FileEdit,
    Layers,
    Lock,
    Plus,
    Search,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { StatCard } from '@/components/dashboard/stat-card';
import { RbmStatusBadge as StatusBadge } from '@/components/rbm/rbm-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate, formatPercent } from '@/lib/utils';
import type { Paginated, Papa, StatutPapa } from '@/types';

interface Stats {
    total: number;
    brouillon: number;
    en_validation: number;
    valides: number;
    clotures: number;
    archives: number;
    annee_min: number;
    annee_max: number;
    papa_actif: {
        id: number;
        annee: number;
        libelle: string;
        statut: string;
        taux_execution_physique: number;
        taux_execution_financier: number;
        date_debut: string | null;
        date_fin: string | null;
    } | null;
}

interface Props {
    papas: Paginated<Papa & {
        valideur: { id: number; name: string } | null;
        createur: { id: number; name: string } | null;
        axes_count: number;
    }>;
    filters: { q: string; statut: string };
    statuts: StatutPapa[];
    stats: Stats;
    can: { create: boolean };
}

export default function PapaIndex({ papas, filters, statuts, stats, can }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [statut, setStatut] = useState(filters.statut ?? 'all');

    const submitSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get('/papa', { q, statut: statut === 'all' ? '' : statut }, { preserveState: true, replace: true });
    };

    const periodeCouverte = stats.annee_min && stats.annee_max
        ? stats.annee_min === stats.annee_max
            ? `Exercice ${stats.annee_max}`
            : `${stats.annee_min} → ${stats.annee_max}`
        : 'Aucun exercice';

    return (
        <AppLayout
            pageTitle="Plans d'Actions Prioritaires Annuels"
            breadcrumbs={[{ label: 'PAPA' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={papas.data} filename="papa" meta={{ total: papas.total ?? papas.data.length, filtres: filters }} />
                    <PdfExportButton reportKey="liste_papas" filtres={filters} />
                    {can.create && (
                        <Button asChild>
                            <Link href="/papa/create">
                                <Plus className="h-4 w-4" />
                                Nouveau PAPA
                            </Link>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                {/* ===== Hero institutionnel ===== */}
                <Card className="overflow-hidden border-primary/30">
                    <div className="relative bg-linear-to-br from-ceeac-blue via-ceeac-blue/90 to-ceeac-blue/70 px-6 py-8 text-white">
                        <div
                            className="absolute inset-0 opacity-10"
                            style={{ backgroundImage: 'radial-gradient(circle at 20% 20%, white 1px, transparent 1px)', backgroundSize: '24px 24px' }}
                        />
                        <div className="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                            <div className="flex items-center gap-5">
                                <img
                                    src="/images/LOGO-CEEAC.jpg"
                                    alt="CEEAC"
                                    className="h-20 w-20 rounded-xl bg-white p-1 object-contain shadow-lg"
                                />
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">
                                        Commission de la CEEAC
                                    </p>
                                    <h1 className="text-2xl font-bold leading-tight md:text-3xl">
                                        Plans d'Actions Prioritaires Annuels
                                    </h1>
                                    <p className="mt-1 text-sm text-white/80">
                                        Référentiel stratégique annuel · Hiérarchie RBM/GAR officielle CEEAC
                                    </p>
                                </div>
                            </div>

                            {stats.papa_actif ? (
                                <div className="grid grid-cols-3 gap-4 rounded-xl bg-white/10 p-4 backdrop-blur-sm md:gap-6">
                                    <HeroMetric label="Exécution physique" value={formatPercent(stats.papa_actif.taux_execution_physique)} />
                                    <HeroMetric label="Exécution financière" value={formatPercent(stats.papa_actif.taux_execution_financier)} />
                                    <HeroMetric label="Total PAPA" value={String(stats.total)} sub={periodeCouverte} />
                                </div>
                            ) : (
                                <div className="rounded-xl bg-white/10 p-4 text-center backdrop-blur-sm">
                                    {can.create ? (
                                        <Button asChild variant="secondary">
                                            <Link href="/papa/create">
                                                <Plus className="h-4 w-4" />
                                                Initier le premier PAPA
                                            </Link>
                                        </Button>
                                    ) : (
                                        <p className="text-sm">Aucun PAPA enregistré.</p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                    {stats.papa_actif && (
                        <CardContent className="flex flex-col gap-3 border-t bg-muted/30 p-4 md:flex-row md:items-center md:justify-between">
                            <div className="flex items-center gap-3">
                                <Badge variant="outline" className="border-primary/40 bg-primary/5 text-primary">
                                    PAPA actif
                                </Badge>
                                <StatusBadge statut={stats.papa_actif.statut} />
                                <Link href={`/papa/${stats.papa_actif.id}`} className="font-semibold hover:underline">
                                    {stats.papa_actif.libelle}
                                </Link>
                                <span className="text-xs text-muted-foreground">Exercice {stats.papa_actif.annee}</span>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <Calendar className="h-3.5 w-3.5" />
                                {stats.papa_actif.date_debut && stats.papa_actif.date_fin && (
                                    <span>
                                        {new Date(stats.papa_actif.date_debut).toLocaleDateString('fr-FR')} →{' '}
                                        {new Date(stats.papa_actif.date_fin).toLocaleDateString('fr-FR')}
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    )}
                </Card>

                {/* ===== Répartition par statut ===== */}
                <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-5">
                    <StatCard
                        title="Brouillon"
                        value={stats.brouillon}
                        icon={FileEdit}
                        iconBg="bg-slate-100 text-slate-700"
                    />
                    <StatCard
                        title="En validation"
                        value={stats.en_validation}
                        icon={ClipboardCheck}
                        iconBg="bg-amber-100 text-amber-700"
                        accent={stats.en_validation > 0 ? 'warning' : 'default'}
                    />
                    <StatCard
                        title="Validés"
                        value={stats.valides}
                        icon={CheckCircle2}
                        iconBg="bg-emerald-100 text-emerald-700"
                        accent="success"
                    />
                    <StatCard
                        title="Clôturés"
                        value={stats.clotures}
                        icon={Lock}
                        iconBg="bg-blue-100 text-blue-700"
                    />
                    <StatCard
                        title="Archivés"
                        value={stats.archives}
                        icon={Archive}
                        iconBg="bg-muted text-muted-foreground"
                    />
                </div>

                {/* ===== Filtres ===== */}
                <Card>
                    <CardContent className="p-4">
                        <form onSubmit={submitSearch} className="flex flex-col gap-2 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={q}
                                    onChange={(e) => setQ(e.target.value)}
                                    placeholder="Rechercher par année, libellé, description…"
                                    className="pl-9"
                                />
                            </div>
                            <Select value={statut} onValueChange={setStatut}>
                                <SelectTrigger className="sm:w-56">
                                    <SelectValue placeholder="Tous les statuts" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les statuts</SelectItem>
                                    {statuts.map((s) => (
                                        <SelectItem key={s} value={s}>
                                            {s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ')}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button type="submit" variant="outline">Filtrer</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* ===== Tableau ===== */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Exercice</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="text-right">Axes</TableHead>
                                    <TableHead>Validation</TableHead>
                                    <TableHead>Créateur</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {papas.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="py-12 text-center text-muted-foreground">
                                            <BookOpen className="mx-auto mb-2 h-8 w-8 opacity-30" />
                                            Aucun PAPA trouvé.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    papas.data.map((p) => (
                                        <TableRow key={p.id} className="hover:bg-accent/40">
                                            <TableCell className="font-semibold tabular-nums">
                                                <div className="flex items-center gap-2">
                                                    {p.annee}
                                                    <span className="font-mono text-[10px] text-muted-foreground">v{p.version}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Link href={`/papa/${p.id}`} className="font-medium hover:underline">
                                                    {p.libelle}
                                                </Link>
                                                {p.description ? (
                                                    <p className="line-clamp-1 text-xs text-muted-foreground">{p.description}</p>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1.5">
                                                    <StatusBadge statut={p.statut} />
                                                    {(p as any).verrouille ? (
                                                        <Lock className="h-3.5 w-3.5 text-muted-foreground" />
                                                    ) : null}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Badge variant="outline" className="tabular-nums">
                                                    <Layers className="mr-1 h-3 w-3" />
                                                    {p.axes_count ?? 0}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {p.date_validation ? (
                                                    <>
                                                        <p>{formatDate(p.date_validation)}</p>
                                                        <p className="text-muted-foreground">{p.valideur?.name ?? '—'}</p>
                                                    </>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs text-muted-foreground">{p.createur?.name ?? '—'}</TableCell>
                                            <TableCell>
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/papa/${p.id}`}>Voir</Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                        <Pagination pagination={papas} label="PAPA" />
                    </CardContent>
                </Card>
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
