import { router } from '@inertiajs/react';
import { CalendarRange, GanttChartSquare, Search, ZoomIn, ZoomOut } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Gantt, GanttLegend, type GanttItem, type GanttZoom } from '@/components/charts/gantt';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Props {
    activites: GanttItem[];
    papas: Array<{ id: number; annee: number; libelle: string }>;
    papa_id: number | null;
}

const ZOOM_LIBELLES: Record<GanttZoom, string> = {
    day: 'Jour',
    week: 'Semaine',
    month: 'Mois',
    quarter: 'Trimestre',
};

const ZOOM_ORDER: GanttZoom[] = ['day', 'week', 'month', 'quarter'];

const STATUT_LIBELLES: Record<string, string> = {
    planifiee: 'Planifiée',
    en_cours: 'En cours',
    realisee: 'Réalisée',
    suspendue: 'Suspendue',
    annulee: 'Annulée',
};

export default function ActivitesGantt({ activites, papas, papa_id }: Props) {
    const [zoom, setZoom] = useState<GanttZoom>('month');
    const [statut, setStatut] = useState<string>('all');
    const [axe, setAxe] = useState<string>('all');
    const [q, setQ] = useState<string>('');
    const [onlyRetard, setOnlyRetard] = useState(false);
    const [onlyJalons, setOnlyJalons] = useState(false);

    const axesUniques = useMemo(() => {
        const set = new Set<string>();
        for (const a of activites) {
            if (a.action_code) set.add(a.action_code);
        }

        return Array.from(set).sort();
    }, [activites]);

    const filtered = useMemo(() => {
        const term = q.trim().toLowerCase();

        return activites.filter((a) => {
            if (statut !== 'all' && a.statut !== statut) return false;
            if (axe !== 'all' && a.action_code !== axe) return false;
            if (onlyRetard && !a.en_retard) return false;
            if (onlyJalons && !a.est_jalon) return false;
            if (term) {
                const hay = `${a.code} ${a.libelle} ${a.action_code ?? ''} ${a.point_focal ?? ''}`.toLowerCase();
                if (!hay.includes(term)) return false;
            }

            return true;
        });
    }, [activites, statut, axe, q, onlyRetard, onlyJalons]);

    const indexZoom = ZOOM_ORDER.indexOf(zoom);

    const zoomIn = () => indexZoom > 0 && setZoom(ZOOM_ORDER[indexZoom - 1]);
    const zoomOut = () => indexZoom < ZOOM_ORDER.length - 1 && setZoom(ZOOM_ORDER[indexZoom + 1]);

    const stats = useMemo(() => {
        const total = activites.length;
        const visible = filtered.length;
        const enRetard = activites.filter((a) => a.en_retard).length;
        const jalons = activites.filter((a) => a.est_jalon).length;
        const realisees = activites.filter((a) => a.statut === 'realisee').length;

        return { total, visible, enRetard, jalons, realisees };
    }, [activites, filtered]);

    return (
        <AppLayout
            pageTitle="Diagramme de Gantt"
            breadcrumbs={[{ label: 'Activités', href: '/activites' }, { label: 'Vue Gantt' }]}
            actions={
                <Select
                    value={String(papa_id ?? '')}
                    onValueChange={(v) => router.get('/activites/gantt', { papa_id: v }, { preserveState: false })}
                >
                    <SelectTrigger className="w-72">
                        <SelectValue placeholder="Choisir un PAPA" />
                    </SelectTrigger>
                    <SelectContent>
                        {papas.map((p) => (
                            <SelectItem key={p.id} value={String(p.id)}>
                                {p.annee} — {p.libelle}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Vue Gantt des activités"
                    description="Lecture calendaire des activités, jalons et échéances opérationnelles."
                />

                {/* Barre de filtres */}
                <Card>
                    <CardContent className="space-y-3 p-4">
                        <div className="grid grid-cols-1 gap-2 md:grid-cols-12">
                            <div className="relative md:col-span-3">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={q}
                                    onChange={(e) => setQ(e.target.value)}
                                    placeholder="Recherche : code, libellé, focal…"
                                    className="pl-9"
                                />
                            </div>

                            <div className="md:col-span-2">
                                <Select value={statut} onValueChange={setStatut}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Statut" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Tous les statuts</SelectItem>
                                        {Object.entries(STATUT_LIBELLES).map(([k, v]) => (
                                            <SelectItem key={k} value={k}>{v}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="md:col-span-2">
                                <Select value={axe} onValueChange={setAxe}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Axe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Tous les axes</SelectItem>
                                        {axesUniques.map((a) => (
                                            <SelectItem key={a} value={a}>{a}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-center gap-1 md:col-span-3">
                                <CalendarRange className="h-4 w-4 shrink-0 text-muted-foreground" />
                                <Select value={zoom} onValueChange={(v) => setZoom(v as GanttZoom)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ZOOM_ORDER.map((z) => (
                                            <SelectItem key={z} value={z}>
                                                {ZOOM_LIBELLES[z]}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Button size="icon" variant="outline" onClick={zoomIn} disabled={indexZoom === 0} title="Zoomer (jour ←)">
                                    <ZoomIn className="h-4 w-4" />
                                </Button>
                                <Button size="icon" variant="outline" onClick={zoomOut} disabled={indexZoom === ZOOM_ORDER.length - 1} title="Dézoomer (trimestre →)">
                                    <ZoomOut className="h-4 w-4" />
                                </Button>
                            </div>

                            <div className="flex items-center gap-2 md:col-span-2">
                                <Button
                                    size="sm"
                                    variant={onlyRetard ? 'destructive' : 'outline'}
                                    onClick={() => setOnlyRetard((v) => !v)}
                                >
                                    Retards ({stats.enRetard})
                                </Button>
                                <Button
                                    size="sm"
                                    variant={onlyJalons ? 'default' : 'outline'}
                                    onClick={() => setOnlyJalons((v) => !v)}
                                >
                                    Jalons ({stats.jalons})
                                </Button>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-3">
                            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <Badge variant="outline">Total : {stats.total}</Badge>
                                <Badge variant="outline">Affichées : {stats.visible}</Badge>
                                <Badge variant="outline">Réalisées : {stats.realisees}</Badge>
                                <Badge variant="outline" className={stats.enRetard > 0 ? 'border-rose-300 text-rose-700' : ''}>
                                    En retard : {stats.enRetard}
                                </Badge>
                                <Badge variant="outline">Jalons : {stats.jalons}</Badge>
                            </div>
                            <GanttLegend />
                        </div>
                    </CardContent>
                </Card>

                {/* Gantt */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2">
                            <GanttChartSquare className="h-5 w-5" />
                            Planification temporelle
                        </CardTitle>
                        <CardDescription>
                            Vue {ZOOM_LIBELLES[zoom].toLowerCase()} · Cliquez une activité pour l'ouvrir
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Gantt items={filtered} zoom={zoom} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
