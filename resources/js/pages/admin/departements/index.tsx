import { Link, router } from '@inertiajs/react';
import { Building2, CheckCircle2, Pencil, Plus, Search, Target, TrendingUp, XCircle } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function DepartementsIndex({ departements, filters, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [actif, setActif] = useState(filters.actif ?? 'all');
    const departementsData = departements.data ?? [];
    const totalAxes = departementsData.reduce((sum: number, d: any) => sum + Number(d.axes_count ?? 0), 0);
    const tauxMoyen = departementsData.length > 0
        ? Math.round(departementsData.reduce((sum: number, d: any) => sum + Number(d.taux_execution_moyen ?? 0), 0) / departementsData.length)
        : 0;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/departements', {
            q,
            actif: actif === 'all' ? '' : actif,
        }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout
            pageTitle="Départements techniques"
            breadcrumbs={[{ label: 'Administration' }, { label: 'Départements' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={departementsData} filename="departements" meta={{ total: departements.total ?? departementsData.length, filtres: filters }} />
                    <PdfExportButton reportKey="liste_departements" />
                    {can.create && (
                        <Button asChild>
                            <Link href="/admin/departements/create"><Plus className="h-4 w-4" />Nouveau Département</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="Administration"
                title="Départements techniques"
                description="Référentiel institutionnel des départements, directions, commissaires et axes RBM portés."
                metrics={[
                    { icon: Building2, label: 'Départements', value: Number(departements.total ?? departementsData.length).toLocaleString('fr-FR') },
                    { icon: CheckCircle2, label: 'Actifs visibles', value: Number(departementsData.filter((d: any) => d.actif).length).toLocaleString('fr-FR') },
                    { icon: Target, label: 'Axes visibles', value: totalAxes.toLocaleString('fr-FR') },
                    { icon: TrendingUp, label: 'Exécution moy.', value: `${tauxMoyen}%` },
                ]}
                footer="Organisation institutionnelle et responsabilités de pilotage"
                footerIcon={Building2}
            />

            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-4">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Code, libellé, description…" className="pl-9" />
                        </div>
                        <Select value={actif} onValueChange={setActif}>
                            <SelectTrigger><SelectValue placeholder="Statut" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous les statuts</SelectItem>
                                <SelectItem value="1">Actifs</SelectItem>
                                <SelectItem value="0">Inactifs</SelectItem>
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
                                <TableHead>Libellé</TableHead>
                                <TableHead>Commissaire</TableHead>
                                <TableHead className="text-right">Directions</TableHead>
                                <TableHead className="text-right">Axes</TableHead>
                                <TableHead className="w-32">Exécution moyenne</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {departementsData.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={8} className="py-12 text-center text-muted-foreground">
                                        <Building2 className="mx-auto mb-3 h-10 w-10 opacity-30" />
                                        <p>Aucun département trouvé.</p>
                                    </TableCell>
                                </TableRow>
                            ) : departementsData.map((d: any) => (
                                <TableRow key={d.id}>
                                    <TableCell className="font-mono text-sm font-semibold">{d.code}</TableCell>
                                    <TableCell>
                                        <Link href={`/admin/departements/${d.id}`} className="font-medium hover:underline">
                                            {d.libelle}
                                        </Link>
                                        {d.description && (
                                            <p className="line-clamp-1 text-xs text-muted-foreground mt-0.5">{d.description}</p>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {d.commissaire ? (
                                            <>
                                                <p>{d.commissaire.name}</p>
                                                <p className="text-xs text-muted-foreground">{d.commissaire.email}</p>
                                            </>
                                        ) : (
                                            <span className="text-xs text-muted-foreground italic">Non désigné</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">{d.directions_count}</TableCell>
                                    <TableCell className="text-right tabular-nums">{d.axes_count}</TableCell>
                                    <TableCell>
                                        <Progress
                                            value={d.taux_execution_moyen}
                                            indicatorClassName={
                                                d.taux_execution_moyen >= 75 ? 'bg-success'
                                                : d.taux_execution_moyen >= 40 ? 'bg-warning'
                                                : 'bg-destructive'
                                            }
                                        />
                                        <p className="mt-1 text-[10px] text-right text-muted-foreground tabular-nums">
                                            {d.taux_execution_moyen.toFixed(1)}%
                                        </p>
                                    </TableCell>
                                    <TableCell>
                                        {d.actif ? (
                                            <Badge variant="success" className="gap-1"><CheckCircle2 className="h-3 w-3" />Actif</Badge>
                                        ) : (
                                            <Badge variant="secondary" className="gap-1"><XCircle className="h-3 w-3" />Inactif</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex gap-1">
                                            <Button size="sm" variant="ghost" asChild>
                                                <Link href={`/admin/departements/${d.id}`}>Voir</Link>
                                            </Button>
                                            <Button size="sm" variant="ghost" asChild title="Modifier">
                                                <Link href={`/admin/departements/${d.id}/edit`}><Pencil className="h-4 w-4" /></Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <Pagination pagination={departements} label="départements" />
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}
