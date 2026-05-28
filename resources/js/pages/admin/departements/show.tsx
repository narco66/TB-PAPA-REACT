import { Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Boxes,
    Building2,
    CheckCircle2,
    Mail,
    Pencil,
    Target,
    Trash2,
    User as UserIcon,
    XCircle,
} from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function DepartementShow({ departement, stats, can }: any) {
    const supprimer = () => {
        if (confirm(`Supprimer définitivement le département « ${departement.code} » ? Cette action est irréversible.`)) {
            router.delete(`/admin/departements/${departement.id}`);
        }
    };

    return (
        <AppLayout
            pageTitle={`${departement.code} — ${departement.libelle}`}
            breadcrumbs={[
                { label: 'Administration' },
                { label: 'Départements', href: '/admin/departements' },
                { label: departement.code },
            ]}
            actions={
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/admin/departements"><ArrowLeft className="h-4 w-4" />Retour</Link>
                    </Button>
                    <PdfQuickButton reportKey="fiche_departement" params={{ departement_id: departement.id }}>Fiche PDF</PdfQuickButton>
                    {can.update && (
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/admin/departements/${departement.id}/edit`}>
                                <Pencil className="h-4 w-4" />Modifier
                            </Link>
                        </Button>
                    )}
                    {can.delete && (
                        <Button variant="outline" size="sm" onClick={supprimer}>
                            <Trash2 className="h-4 w-4 text-destructive" />
                            <span className="text-destructive">Supprimer</span>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail département"
                    description="Vue institutionnelle des directions, responsables et axes portés."
                />
                {/* Carte d'identité */}
                <Card>
                    <CardHeader>
                        <div className="flex items-start gap-4">
                            <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-ceeac-blue text-white">
                                <Building2 className="h-7 w-7" />
                            </div>
                            <div className="flex-1">
                                <div className="mb-1 flex items-center gap-2">
                                    <span className="font-mono text-xs text-muted-foreground">{departement.code}</span>
                                    {departement.actif ? (
                                        <Badge variant="success" className="gap-1"><CheckCircle2 className="h-3 w-3" />Actif</Badge>
                                    ) : (
                                        <Badge variant="secondary" className="gap-1"><XCircle className="h-3 w-3" />Inactif</Badge>
                                    )}
                                </div>
                                <CardTitle className="text-2xl">{departement.libelle}</CardTitle>
                                {departement.description && (
                                    <CardDescription className="mt-2 leading-relaxed">{departement.description}</CardDescription>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {/* Stats */}
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <StatCard icon={Building2} label="Directions" value={stats.nb_directions} sub={`${stats.nb_directions_techniques} techn. · ${stats.nb_directions_appui} appui`} color="bg-blue-100 text-blue-700" />
                    <StatCard icon={Target} label="Axes RBM" value={stats.nb_axes} sub="Sous mandat" color="bg-emerald-100 text-emerald-700" />
                    <StatCard icon={Boxes} label="Exécution moyenne" value={`${stats.taux_execution_moyen.toFixed(1)}%`} sub="Moyenne des axes" color="bg-amber-100 text-amber-700" />
                    <StatCard icon={UserIcon} label="Commissaire" value={departement.commissaire ? '1' : '0'} sub={departement.commissaire?.name ?? 'Non désigné'} color="bg-violet-100 text-violet-700" />
                </div>

                {/* Commissaire */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <UserIcon className="h-4 w-4" />Commissaire en charge (Chef de Département)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {departement.commissaire ? (
                            <div className="flex items-center gap-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                    {departement.commissaire.name.split(' ').map((p: string) => p[0]).slice(0, 2).join('').toUpperCase()}
                                </div>
                                <div className="flex-1">
                                    <p className="font-semibold">{departement.commissaire.name}</p>
                                    <p className="text-sm text-muted-foreground">{departement.commissaire.fonction ?? '—'}</p>
                                    <div className="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                                        <span className="flex items-center gap-1"><Mail className="h-3 w-3" />{departement.commissaire.email}</span>
                                        {departement.commissaire.matricule && (
                                            <span className="font-mono">Mat. {departement.commissaire.matricule}</span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground italic">
                                Aucun commissaire désigné. La <Link href={`/admin/departements/${departement.id}/edit`} className="text-primary hover:underline">configuration du département</Link> permet d'en assigner un.
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Directions rattachées */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Building2 className="h-4 w-4" />Directions rattachées ({departement.directions.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Directeur</TableHead>
                                    <TableHead>État</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {departement.directions.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-6 text-center text-sm text-muted-foreground">
                                            Aucune direction rattachée à ce département.
                                        </TableCell>
                                    </TableRow>
                                ) : departement.directions.map((d: any) => (
                                    <TableRow key={d.id}>
                                        <TableCell className="font-mono text-xs font-semibold">{d.code}</TableCell>
                                        <TableCell>{d.libelle}</TableCell>
                                        <TableCell>
                                            <Badge variant={d.type === 'technique' ? 'default' : 'secondary'}>
                                                {d.type === 'technique' ? 'Technique' : 'Appui & soutien'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-sm">{d.directeur?.name ?? '—'}</TableCell>
                                        <TableCell>
                                            {d.actif ? (
                                                <Badge variant="success">Actif</Badge>
                                            ) : (
                                                <Badge variant="secondary">Inactif</Badge>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Axes RBM portés */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Target className="h-4 w-4" />Axes stratégiques portés ({departement.axes.length})
                        </CardTitle>
                        <CardDescription>Axes RBM rattachés à ce département dans les PAPAs</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>PAPA</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead className="w-32">Avancement</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {departement.axes.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-6 text-center text-sm text-muted-foreground">
                                            Aucun axe stratégique défini pour ce département.
                                        </TableCell>
                                    </TableRow>
                                ) : departement.axes.map((a: any) => (
                                    <TableRow key={a.id}>
                                        <TableCell className="font-mono text-xs font-semibold">{a.code}</TableCell>
                                        <TableCell>
                                            <Link href={`/rbm/axes/${a.id}`} className="font-medium hover:underline">
                                                {a.libelle}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="text-sm">PAPA {a.papa?.annee}</TableCell>
                                        <TableCell><RbmStatusBadge statut={a.statut} /></TableCell>
                                        <TableCell>
                                            <Progress
                                                value={a.taux_execution}
                                                indicatorClassName={
                                                    a.taux_execution >= 75 ? 'bg-success'
                                                    : a.taux_execution >= 40 ? 'bg-warning'
                                                    : 'bg-destructive'
                                                }
                                            />
                                            <p className="mt-1 text-[10px] text-right text-muted-foreground tabular-nums">
                                                {Number(a.taux_execution).toFixed(1)}%
                                            </p>
                                        </TableCell>
                                        <TableCell>
                                            <Button size="sm" variant="ghost" asChild>
                                                <Link href={`/rbm/axes/${a.id}`}>Voir</Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {can.delete === false && (departement.directions.length > 0 || departement.axes.length > 0) && (
                    <Card className="border-warning/40 bg-warning/5">
                        <CardContent className="text-sm p-4 text-muted-foreground">
                            <strong>Note :</strong> Ce département ne peut pas être supprimé car il contient
                            des directions ({departement.directions.length}) ou des axes ({departement.axes.length}).
                            Désaffectez-les ou supprimez-les d'abord.
                        </CardContent>
                    </Card>
                )}
                </div>
</AppLayout>
    );
}

function StatCard({ icon: Icon, label, value, sub, color }: any) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3 p-4">
                <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${color}`}>
                    <Icon className="h-5 w-5" />
                </div>
                <div className="min-w-0">
                    <p className="text-xs text-muted-foreground">{label}</p>
                    <p className="text-xl font-bold tabular-nums truncate">{value}</p>
                    <p className="text-xs text-muted-foreground truncate">{sub}</p>
                </div>
            </CardContent>
        </Card>
    );
}
