import { Link, router } from '@inertiajs/react';
import { BarChart3, Plus, Search, TrendingDown, TrendingUp } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatPercent } from '@/lib/utils';

interface Props {
    indicateurs: any;
    filters: { q: string; frequence: string; type: string };
    can: { create: boolean };
}

const FREQUENCES: Record<string, string> = {
    mensuelle: 'Mensuelle',
    trimestrielle: 'Trimestrielle',
    semestrielle: 'Semestrielle',
    annuelle: 'Annuelle',
};

export default function IndicateursIndex({ indicateurs, filters, can }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [freq, setFreq] = useState(filters.frequence ?? 'all');
    const [type, setType] = useState(filters.type ?? 'all');
    const indicateursData = indicateurs.data ?? [];
    const tauxMoyen = indicateursData.length > 0
        ? Math.round(indicateursData.reduce((sum: number, i: any) => sum + Number(i.taux_realisation ?? 0), 0) / indicateursData.length)
        : 0;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/indicateurs', {
            q,
            frequence: freq === 'all' ? '' : freq,
            type: type === 'all' ? '' : type,
        }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout
            pageTitle="Indicateurs de résultats (KPI)"
            breadcrumbs={[{ label: 'Indicateurs' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={indicateursData} filename="indicateurs" meta={{ total: indicateurs.total ?? indicateursData.length, filtres: filters }} />
                    <PdfExportButton reportKey="liste_indicateurs" filtres={filters} />
                    {can.create && (
                        <Button asChild>
                            <Link href="/indicateurs/create"><Plus className="h-4 w-4" />Nouvel indicateur</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="Suivi-évaluation"
                title="Indicateurs de résultats (KPI)"
                description="Mesure des résultats, cibles, tendances et fréquences de collecte pour le suivi RBM/GAR."
                metrics={[
                    { icon: BarChart3, label: 'Indicateurs', value: Number(indicateurs.total ?? indicateursData.length).toLocaleString('fr-FR') },
                    { icon: TrendingUp, label: 'Moyenne visible', value: `${tauxMoyen}%` },
                    { icon: TrendingDown, label: 'À risque visibles', value: Number(indicateursData.filter((i: any) => Number(i.taux_realisation ?? 0) < 40).length).toLocaleString('fr-FR') },
                    { icon: BarChart3, label: 'Fréquences', value: Object.keys(FREQUENCES).length.toLocaleString('fr-FR') },
                ]}
                footer="Pilotage des cibles, valeurs actuelles et tendances de performance"
                footerIcon={BarChart3}
            />

            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-5">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Code, libellé…" className="pl-9" />
                        </div>
                        <Select value={freq} onValueChange={setFreq}>
                            <SelectTrigger><SelectValue placeholder="Fréquence" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Toutes fréquences</SelectItem>
                                {Object.entries(FREQUENCES).map(([k, v]) => (
                                    <SelectItem key={k} value={k}>{v}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={type} onValueChange={setType}>
                            <SelectTrigger><SelectValue placeholder="Type" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous types</SelectItem>
                                <SelectItem value="quantitatif">Quantitatif</SelectItem>
                                <SelectItem value="qualitatif">Qualitatif</SelectItem>
                            </SelectContent>
                        </Select>
                        <Button type="submit" variant="outline">Filtrer</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Libellé / Résultat rattaché</TableHead>
                                <TableHead>Baseline → Cible</TableHead>
                                <TableHead>Valeur</TableHead>
                                <TableHead>Tendance</TableHead>
                                <TableHead>Fréquence</TableHead>
                                <TableHead className="w-32">Réalisation</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {indicateurs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="py-8 text-center text-muted-foreground">
                                        <BarChart3 className="mx-auto mb-2 h-8 w-8 opacity-30" />
                                        Aucun indicateur défini.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                indicateurs.data.map((i: any) => (
                                    <TableRow key={i.id}>
                                        <TableCell className="font-mono text-xs">{i.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/indicateurs/${i.id}`} className="font-medium hover:underline line-clamp-1">
                                                {i.libelle}
                                            </Link>
                                            <p className="text-xs text-muted-foreground line-clamp-1">
                                                {i.resultat_attendu?.objectif_immediat?.action_prioritaire?.code} · {i.resultat_attendu?.libelle}
                                            </p>
                                        </TableCell>
                                        <TableCell className="tabular-nums text-xs">
                                            {i.baseline ?? '—'} → <span className="font-medium">{i.cible ?? '—'}</span> {i.unite ?? ''}
                                        </TableCell>
                                        <TableCell className="tabular-nums text-sm font-semibold">
                                            {i.valeur_actuelle ?? '—'} {i.unite ?? ''}
                                        </TableCell>
                                        <TableCell>
                                            {i.tendance === 'hausse' && <TrendingUp className="h-4 w-4 text-success" />}
                                            {i.tendance === 'baisse' && <TrendingDown className="h-4 w-4 text-destructive" />}
                                            {i.tendance === 'stable' && <span className="text-muted-foreground">→</span>}
                                            {i.tendance === 'inconnue' && <span className="text-muted-foreground">—</span>}
                                        </TableCell>
                                        <TableCell className="text-xs">{FREQUENCES[i.frequence_collecte] ?? i.frequence_collecte}</TableCell>
                                        <TableCell>
                                            <Progress
                                                value={Math.min(100, Math.max(0, i.taux_realisation ?? 0))}
                                                indicatorClassName={
                                                    (i.taux_realisation ?? 0) >= 75 ? 'bg-success'
                                                        : (i.taux_realisation ?? 0) >= 40 ? 'bg-warning'
                                                        : 'bg-destructive'
                                                }
                                            />
                                            <p className="mt-1 text-[10px] text-right text-muted-foreground tabular-nums">
                                                {formatPercent(i.taux_realisation)}
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            <Button size="sm" variant="ghost" asChild>
                                                <Link href={`/indicateurs/${i.id}`}>Saisir</Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                    <Pagination pagination={indicateurs} label="indicateurs" />
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}
