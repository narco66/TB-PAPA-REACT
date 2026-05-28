import { Link, router } from '@inertiajs/react';
import { AlertTriangle, BarChart3, Calendar, GanttChartSquare, Plus, Search, TrendingUp } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { StatusBadge } from '@/components/layout/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate, formatPercent } from '@/lib/utils';

interface Props {
    activites: any;
    papas: Array<{ id: number; annee: number; libelle: string }>;
    filters: { q: string; statut: string; papa_id: number | null; retard: boolean };
    can: { create: boolean };
}

export default function ActivitesIndex({ activites, papas, filters, can }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [statut, setStatut] = useState(filters.statut ?? 'all');
    const [papaId, setPapaId] = useState(String(filters.papa_id ?? 'all'));
    const [retard, setRetard] = useState(filters.retard);
    const activitesData = activites.data ?? [];
    const enRetard = activitesData.filter((a: any) => a.en_retard).length;
    const tauxMoyen = activitesData.length > 0
        ? Math.round(activitesData.reduce((sum: number, a: any) => sum + Number(a.avancement ?? 0), 0) / activitesData.length)
        : 0;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/activites', {
            q,
            statut: statut === 'all' ? '' : statut,
            papa_id: papaId === 'all' ? '' : papaId,
            retard: retard ? 1 : 0,
        }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout
            pageTitle="Activités planifiées"
            breadcrumbs={[{ label: 'Activités' }]}
            actions={
                <div className="flex gap-2">
                    <PdfExportButton reportKey="liste_activites" filtres={filters} />
                    <Button asChild variant="outline">
                        <Link href="/activites/gantt"><GanttChartSquare className="h-4 w-4" />Vue Gantt</Link>
                    </Button>
                    {can.create && (
                        <Button asChild>
                            <Link href="/activites/create"><Plus className="h-4 w-4" />Nouvelle activité</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="Exécution opérationnelle"
                title="Activités planifiées"
                description="Suivi des activités du PAPA, des jalons, risques, points focaux et échéances d'exécution."
                metrics={[
                    { icon: GanttChartSquare, label: 'Activités', value: Number(activites.total ?? activitesData.length).toLocaleString('fr-FR') },
                    { icon: AlertTriangle, label: 'En retard visibles', value: enRetard.toLocaleString('fr-FR') },
                    { icon: Calendar, label: 'PAPA suivis', value: Number(papas.length ?? 0).toLocaleString('fr-FR') },
                    { icon: TrendingUp, label: 'Avancement moy.', value: `${tauxMoyen}%` },
                ]}
                footer="Supervision opérationnelle des activités, jalons et responsabilités"
                footerIcon={BarChart3}
            />

            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-5">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Code, libellé…" className="pl-9" />
                        </div>
                        <Select value={papaId} onValueChange={setPapaId}>
                            <SelectTrigger><SelectValue placeholder="PAPA" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les PAPA</SelectItem>
                                {papas.map((p) => (<SelectItem key={p.id} value={String(p.id)}>{p.annee}</SelectItem>))}
                            </SelectContent>
                        </Select>
                        <Select value={statut} onValueChange={setStatut}>
                            <SelectTrigger><SelectValue placeholder="Statut" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous statuts</SelectItem>
                                <SelectItem value="planifiee">Planifiée</SelectItem>
                                <SelectItem value="en_cours">En cours</SelectItem>
                                <SelectItem value="realisee">Réalisée</SelectItem>
                                <SelectItem value="suspendue">Suspendue</SelectItem>
                                <SelectItem value="annulee">Annulée</SelectItem>
                            </SelectContent>
                        </Select>
                        <div className="flex gap-2">
                            <label className="flex flex-1 items-center gap-2 rounded-md border px-3 text-sm cursor-pointer hover:bg-accent">
                                <input type="checkbox" checked={retard} onChange={(e) => setRetard(e.target.checked)} className="h-4 w-4" />
                                <span>En retard</span>
                            </label>
                            <Button type="submit" variant="outline">OK</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Activité / Action</TableHead>
                                <TableHead>Période</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead>Risque</TableHead>
                                <TableHead className="w-32">Avancement</TableHead>
                                <TableHead>Point focal</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {activitesData.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="py-8 text-center text-muted-foreground">Aucune activité.</TableCell></TableRow>
                            ) : activitesData.map((a: any) => (
                                <TableRow key={a.id} className={a.en_retard ? 'bg-destructive/5' : ''}>
                                    <TableCell className="font-mono text-xs">
                                        <div className="flex items-center gap-1">
                                            {a.est_jalon && <span title="Jalon" className="text-warning">◆</span>}
                                            {a.code}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Link href={`/activites/${a.id}`} className="font-medium hover:underline line-clamp-1">
                                            {a.libelle}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {a.action_prioritaire?.code} — {a.action_prioritaire?.papa?.annee}
                                        </p>
                                    </TableCell>
                                    <TableCell className="text-xs whitespace-nowrap">
                                        {formatDate(a.date_debut_prevue)} → {formatDate(a.date_fin_prevue)}
                                        {a.en_retard && (
                                            <div className="flex items-center gap-1 text-destructive font-medium mt-0.5">
                                                <AlertTriangle className="h-3 w-3" />En retard
                                            </div>
                                        )}
                                    </TableCell>
                                    <TableCell><StatusBadge statut={a.statut} /></TableCell>
                                    <TableCell>
                                        <Badge variant={
                                            a.niveau_risque === 'critique' ? 'destructive'
                                                : a.niveau_risque === 'eleve' ? 'warning'
                                                : a.niveau_risque === 'moyen' ? 'secondary'
                                                : 'outline'
                                        }>{a.niveau_risque}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Progress value={a.avancement} indicatorClassName={a.avancement >= 75 ? 'bg-success' : a.avancement >= 40 ? 'bg-warning' : 'bg-destructive'} />
                                        <p className="mt-1 text-[10px] text-right tabular-nums text-muted-foreground">{formatPercent(a.avancement)}</p>
                                    </TableCell>
                                    <TableCell className="text-xs">{a.point_focal?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        <Button size="sm" variant="ghost" asChild><Link href={`/activites/${a.id}`}>Voir</Link></Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}
