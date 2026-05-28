import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import { cn, formatDate } from '@/lib/utils';

export type GanttZoom = 'day' | 'week' | 'month' | 'quarter';

export interface GanttItem {
    id: number;
    code: string;
    libelle: string;
    date_debut: string | null;
    date_fin: string | null;
    avancement: number;
    statut: string;
    niveau_risque: string;
    est_jalon: boolean;
    action_code?: string | null;
    departement?: string | null;
    point_focal?: string | null;
    en_retard?: boolean;
}

interface Props {
    items: GanttItem[];
    zoom?: GanttZoom;
    minDate?: string;
    maxDate?: string;
}

const STATUT_COLORS: Record<string, string> = {
    planifiee: 'bg-slate-400',
    en_cours: 'bg-blue-500',
    realisee: 'bg-emerald-500',
    suspendue: 'bg-amber-500',
    annulee: 'bg-rose-300',
};

const PIXELS_PER_DAY: Record<GanttZoom, number> = {
    day: 36,
    week: 14,
    month: 5,
    quarter: 2,
};

const LEFT_COLUMN_PX = 320;
const ROW_HEIGHT = 36;
const HEADER_HEIGHT_PRIMARY = 24;
const HEADER_HEIGHT_SECONDARY = 22;

const MS_PER_DAY = 86_400_000;

const JOUR_FR = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
const MOIS_FR = ['Janv', 'Févr', 'Mars', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];

function startOfDay(d: Date): Date {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);

    return x;
}

function addDays(d: Date, n: number): Date {
    const x = new Date(d);
    x.setDate(x.getDate() + n);

    return x;
}

function daysBetween(a: Date, b: Date): number {
    return Math.round((b.getTime() - a.getTime()) / MS_PER_DAY);
}

function startOfWeek(d: Date): Date {
    const x = startOfDay(d);
    const day = x.getDay();
    const diff = (day + 6) % 7; // semaine commence lundi
    x.setDate(x.getDate() - diff);

    return x;
}

function isoWeekNumber(d: Date): number {
    const target = new Date(d.valueOf());
    const dayNr = (d.getDay() + 6) % 7;
    target.setDate(target.getDate() - dayNr + 3);
    const firstThursday = target.valueOf();
    target.setMonth(0, 1);
    if (target.getDay() !== 4) {
        target.setMonth(0, 1 + ((4 - target.getDay()) + 7) % 7);
    }

    return 1 + Math.ceil((firstThursday - target.valueOf()) / (7 * MS_PER_DAY));
}

function quarterOf(d: Date): number {
    return Math.floor(d.getMonth() / 3) + 1;
}

export function Gantt({ items, zoom = 'month', minDate, maxDate }: Props) {
    const range = useMemo(() => {
        const dates: Date[] = [];
        for (const i of items) {
            if (i.date_debut) dates.push(new Date(i.date_debut));
            if (i.date_fin) dates.push(new Date(i.date_fin));
        }
        if (minDate) dates.push(new Date(minDate));
        if (maxDate) dates.push(new Date(maxDate));

        let start: Date;
        let end: Date;
        if (dates.length === 0) {
            const now = new Date();
            start = new Date(now.getFullYear(), 0, 1);
            end = new Date(now.getFullYear(), 11, 31);
        } else {
            start = startOfDay(new Date(Math.min(...dates.map((d) => d.getTime()))));
            end = startOfDay(new Date(Math.max(...dates.map((d) => d.getTime()))));
        }
        start = addDays(start, -7);
        end = addDays(end, 7);

        const days = Math.max(1, daysBetween(start, end));

        return { start, end, days };
    }, [items, minDate, maxDate]);

    const pxPerDay = PIXELS_PER_DAY[zoom];
    const timelineWidth = range.days * pxPerDay;

    const today = startOfDay(new Date());
    const todayPx = daysBetween(range.start, today) * pxPerDay;
    const todayInRange = todayPx >= 0 && todayPx <= timelineWidth;

    const headers = useMemo(() => buildHeaders(range.start, range.days, zoom, pxPerDay), [range, zoom, pxPerDay]);

    if (items.length === 0) {
        return (
            <div className="rounded-md border bg-muted/30 p-12 text-center text-sm text-muted-foreground">
                Aucune activité à afficher dans le diagramme de Gantt.
            </div>
        );
    }

    const headerHeight = HEADER_HEIGHT_PRIMARY + HEADER_HEIGHT_SECONDARY;

    return (
        <div className="overflow-x-auto rounded-md border bg-card">
            <div style={{ width: LEFT_COLUMN_PX + timelineWidth, minWidth: '100%' }}>
                {/* En-tête timeline */}
                <div className="sticky top-0 z-20 flex border-b bg-card">
                    <div
                        className="shrink-0 border-r bg-muted/40 px-3"
                        style={{ width: LEFT_COLUMN_PX, height: headerHeight }}
                    >
                        <p className="pt-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Activité · {items.length}
                        </p>
                    </div>
                    <div className="relative" style={{ width: timelineWidth }}>
                        {/* Header primaire (mois/trimestre/année selon zoom) */}
                        <div className="relative" style={{ height: HEADER_HEIGHT_PRIMARY }}>
                            {headers.primary.map((h, i) => (
                                <div
                                    key={`p${i}`}
                                    className="absolute top-0 flex items-center border-l border-border/60 bg-muted/40 px-2 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground"
                                    style={{ left: h.offsetPx, width: h.widthPx, height: HEADER_HEIGHT_PRIMARY }}
                                >
                                    <span className="truncate">{h.label}</span>
                                </div>
                            ))}
                        </div>
                        {/* Header secondaire (jours/semaines/mois selon zoom) */}
                        <div className="relative border-t" style={{ height: HEADER_HEIGHT_SECONDARY }}>
                            {headers.secondary.map((h, i) => (
                                <div
                                    key={`s${i}`}
                                    className={cn(
                                        'absolute top-0 flex items-center justify-center border-l border-border/40 text-[10px] tabular-nums text-muted-foreground',
                                        h.isWeekend && 'bg-muted/30',
                                        h.isToday && 'bg-rose-50 font-bold text-rose-700',
                                    )}
                                    style={{ left: h.offsetPx, width: h.widthPx, height: HEADER_HEIGHT_SECONDARY }}
                                >
                                    {h.label}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Corps */}
                <div className="relative">
                    {/* Grille verticale + zones week-end + ligne du jour */}
                    <div
                        className="pointer-events-none absolute top-0 bottom-0"
                        style={{ left: LEFT_COLUMN_PX, width: timelineWidth }}
                    >
                        {headers.secondary.map((h, i) =>
                            h.isWeekend ? (
                                <div
                                    key={`bg${i}`}
                                    className="absolute top-0 bottom-0 bg-muted/15"
                                    style={{ left: h.offsetPx, width: h.widthPx }}
                                />
                            ) : null,
                        )}
                        {headers.primary.map((h, i) => (
                            <div
                                key={`gl${i}`}
                                className="absolute top-0 bottom-0 border-l border-border/40"
                                style={{ left: h.offsetPx }}
                            />
                        ))}
                        {todayInRange && (
                            <div
                                className="absolute top-0 bottom-0 z-10 w-px bg-rose-500"
                                style={{ left: todayPx }}
                            >
                                <div className="sticky top-0 -ml-7 -translate-y-full pt-1">
                                    <span className="rounded bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white whitespace-nowrap shadow">
                                        Aujourd'hui
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Lignes activités */}
                    {items.map((it, idx) => {
                        const startD = it.date_debut ? startOfDay(new Date(it.date_debut)) : range.start;
                        const endD = it.date_fin ? startOfDay(new Date(it.date_fin)) : range.end;
                        const offsetPx = daysBetween(range.start, startD) * pxPerDay;
                        const widthPx = Math.max(pxPerDay, daysBetween(startD, endD) * pxPerDay);

                        const barColor = it.en_retard
                            ? 'bg-rose-500'
                            : STATUT_COLORS[it.statut] ?? 'bg-slate-400';

                        return (
                            <div
                                key={it.id}
                                className={cn(
                                    'group relative flex border-b border-border/40 hover:bg-accent/40',
                                    idx % 2 === 1 && 'bg-muted/10',
                                )}
                                style={{ height: ROW_HEIGHT }}
                            >
                                <div
                                    className="sticky left-0 z-10 shrink-0 border-r bg-card px-3 py-1.5 group-hover:bg-accent/40"
                                    style={{ width: LEFT_COLUMN_PX }}
                                >
                                    <Link href={`/activites/${it.id}`} className="block hover:underline">
                                        <p className="line-clamp-1 text-sm font-medium leading-tight">
                                            {it.est_jalon && <span className="mr-1 text-amber-600">◆</span>}
                                            {it.libelle}
                                        </p>
                                        <p className="line-clamp-1 text-[10px] text-muted-foreground">
                                            <span className="font-mono">{it.code}</span>
                                            {it.action_code ? ` · ${it.action_code}` : ''}
                                            {it.departement ? ` · ${it.departement}` : ''}
                                            {it.point_focal ? ` · ${it.point_focal}` : ''}
                                        </p>
                                    </Link>
                                </div>
                                <div className="relative flex-1" style={{ width: timelineWidth, height: ROW_HEIGHT }}>
                                    {it.est_jalon ? (
                                        <div
                                            className="absolute top-1/2 -translate-y-1/2 -translate-x-1/2"
                                            style={{ left: offsetPx }}
                                            title={`${formatDate(it.date_debut)} (jalon)`}
                                        >
                                            <div className="h-4 w-4 rotate-45 bg-amber-500 shadow ring-1 ring-amber-700" />
                                        </div>
                                    ) : (
                                        <div
                                            className={cn(
                                                'absolute top-1.5 bottom-1.5 rounded shadow-sm transition-all overflow-hidden',
                                                barColor,
                                            )}
                                            style={{ left: offsetPx, width: widthPx }}
                                            title={`${formatDate(it.date_debut)} → ${formatDate(it.date_fin)} · ${Math.round(it.avancement)}%`}
                                        >
                                            <div
                                                className="h-full bg-white/35"
                                                style={{ width: `${Math.min(100, it.avancement)}%` }}
                                            />
                                            {widthPx > 36 && (
                                                <span className="absolute inset-0 flex items-center justify-center px-1 text-[10px] font-semibold text-white whitespace-nowrap">
                                                    {Math.round(it.avancement)}%
                                                </span>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

interface HeaderCell {
    label: string;
    offsetPx: number;
    widthPx: number;
    isWeekend?: boolean;
    isToday?: boolean;
}

function buildHeaders(start: Date, days: number, zoom: GanttZoom, pxPerDay: number): { primary: HeaderCell[]; secondary: HeaderCell[] } {
    const end = addDays(start, days);
    const today = startOfDay(new Date());

    if (zoom === 'day') {
        // Primary = mois ; Secondary = jours
        const primary: HeaderCell[] = [];
        const cur = new Date(start.getFullYear(), start.getMonth(), 1);
        while (cur < end) {
            const next = new Date(cur.getFullYear(), cur.getMonth() + 1, 1);
            const startPx = Math.max(0, daysBetween(start, cur) * pxPerDay);
            const endPx = Math.min(days * pxPerDay, daysBetween(start, next) * pxPerDay);
            primary.push({
                label: `${MOIS_FR[cur.getMonth()]} ${cur.getFullYear()}`,
                offsetPx: startPx,
                widthPx: endPx - startPx,
            });
            cur.setMonth(cur.getMonth() + 1);
        }

        const secondary: HeaderCell[] = [];
        for (let i = 0; i < days; i++) {
            const d = addDays(start, i);
            secondary.push({
                label: `${JOUR_FR[d.getDay()]}${d.getDate()}`,
                offsetPx: i * pxPerDay,
                widthPx: pxPerDay,
                isWeekend: d.getDay() === 0 || d.getDay() === 6,
                isToday: d.getTime() === today.getTime(),
            });
        }

        return { primary, secondary };
    }

    if (zoom === 'week') {
        // Primary = mois ; Secondary = semaines
        const primary: HeaderCell[] = [];
        const cur = new Date(start.getFullYear(), start.getMonth(), 1);
        while (cur < end) {
            const next = new Date(cur.getFullYear(), cur.getMonth() + 1, 1);
            const startPx = Math.max(0, daysBetween(start, cur) * pxPerDay);
            const endPx = Math.min(days * pxPerDay, daysBetween(start, next) * pxPerDay);
            primary.push({
                label: `${MOIS_FR[cur.getMonth()]} ${cur.getFullYear()}`,
                offsetPx: startPx,
                widthPx: endPx - startPx,
            });
            cur.setMonth(cur.getMonth() + 1);
        }

        const secondary: HeaderCell[] = [];
        let cursor = startOfWeek(start);
        while (cursor < end) {
            const nextWeek = addDays(cursor, 7);
            const offsetPx = daysBetween(start, cursor) * pxPerDay;
            const widthPx = 7 * pxPerDay;
            secondary.push({
                label: `S${isoWeekNumber(cursor).toString().padStart(2, '0')}`,
                offsetPx,
                widthPx,
            });
            cursor = nextWeek;
        }

        return { primary, secondary };
    }

    if (zoom === 'month') {
        // Primary = trimestres ; Secondary = mois
        const primary: HeaderCell[] = [];
        const cur = new Date(start.getFullYear(), Math.floor(start.getMonth() / 3) * 3, 1);
        while (cur < end) {
            const next = new Date(cur.getFullYear(), cur.getMonth() + 3, 1);
            const startPx = Math.max(0, daysBetween(start, cur) * pxPerDay);
            const endPx = Math.min(days * pxPerDay, daysBetween(start, next) * pxPerDay);
            primary.push({
                label: `T${quarterOf(cur)} ${cur.getFullYear()}`,
                offsetPx: startPx,
                widthPx: endPx - startPx,
            });
            cur.setMonth(cur.getMonth() + 3);
        }

        const secondary: HeaderCell[] = [];
        const cur2 = new Date(start.getFullYear(), start.getMonth(), 1);
        while (cur2 < end) {
            const next = new Date(cur2.getFullYear(), cur2.getMonth() + 1, 1);
            const offsetPx = Math.max(0, daysBetween(start, cur2) * pxPerDay);
            const widthPx = Math.min(days * pxPerDay, daysBetween(start, next) * pxPerDay) - offsetPx;
            secondary.push({
                label: MOIS_FR[cur2.getMonth()],
                offsetPx,
                widthPx,
            });
            cur2.setMonth(cur2.getMonth() + 1);
        }

        return { primary, secondary };
    }

    // zoom === 'quarter' : Primary = années ; Secondary = trimestres
    const primary: HeaderCell[] = [];
    const cur = new Date(start.getFullYear(), 0, 1);
    while (cur < end) {
        const next = new Date(cur.getFullYear() + 1, 0, 1);
        const startPx = Math.max(0, daysBetween(start, cur) * pxPerDay);
        const endPx = Math.min(days * pxPerDay, daysBetween(start, next) * pxPerDay);
        primary.push({
            label: String(cur.getFullYear()),
            offsetPx: startPx,
            widthPx: endPx - startPx,
        });
        cur.setFullYear(cur.getFullYear() + 1);
    }

    const secondary: HeaderCell[] = [];
    const cur2 = new Date(start.getFullYear(), Math.floor(start.getMonth() / 3) * 3, 1);
    while (cur2 < end) {
        const next = new Date(cur2.getFullYear(), cur2.getMonth() + 3, 1);
        const offsetPx = Math.max(0, daysBetween(start, cur2) * pxPerDay);
        const widthPx = Math.min(days * pxPerDay, daysBetween(start, next) * pxPerDay) - offsetPx;
        secondary.push({
            label: `T${quarterOf(cur2)}`,
            offsetPx,
            widthPx,
        });
        cur2.setMonth(cur2.getMonth() + 3);
    }

    return { primary, secondary };
}

export function GanttLegend() {
    return (
        <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
            <span className="font-semibold">Légende :</span>
            <span className="flex items-center gap-1.5"><span className="h-3 w-4 rounded bg-blue-500" />En cours</span>
            <span className="flex items-center gap-1.5"><span className="h-3 w-4 rounded bg-emerald-500" />Réalisée</span>
            <span className="flex items-center gap-1.5"><span className="h-3 w-4 rounded bg-slate-400" />Planifiée</span>
            <span className="flex items-center gap-1.5"><span className="h-3 w-4 rounded bg-amber-500" />Suspendue</span>
            <span className="flex items-center gap-1.5"><span className="h-3 w-4 rounded bg-rose-500" />En retard</span>
            <span className="flex items-center gap-1.5"><span className="h-3 w-3 rotate-45 bg-amber-500" />Jalon</span>
            <span className="flex items-center gap-1.5"><span className="h-3 w-px bg-rose-500" />Aujourd'hui</span>
        </div>
    );
}
