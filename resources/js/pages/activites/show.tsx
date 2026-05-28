import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Calendar, ClipboardList, LoaderCircle, User } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { StatusBadge } from '@/components/layout/status-badge';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatPercent } from '@/lib/utils';

export default function ActiviteShow({ activite, can }: { activite: any; can: any }) {
    const { data, setData, post, processing, errors } = useForm({
        avancement: activite.avancement ?? 0,
        statut: activite.statut,
        commentaire: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/activites/${activite.id}/avancement`);
    };

    return (
        <AppLayout
            pageTitle={`${activite.code} — ${activite.libelle}`}
            breadcrumbs={[{ label: 'Activités', href: '/activites' }, { label: activite.code }]}
            actions={
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/activites"><ArrowLeft className="h-4 w-4" />Retour</Link>
                    </Button>
                    <PdfQuickButton reportKey="fiche_activite" params={{ activite_id: activite.id }}>Fiche PDF</PdfQuickButton>
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail activité"
                    description="Consultation de l'exécution, des responsabilités, livrables et risques de l'activité."
                />
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="mb-2 flex flex-wrap items-center gap-2">
                                    <span className="font-mono text-xs text-muted-foreground">{activite.code}</span>
                                    <StatusBadge statut={activite.statut} />
                                    <Badge variant={
                                        activite.niveau_risque === 'critique' ? 'destructive'
                                            : activite.niveau_risque === 'eleve' ? 'warning'
                                            : 'secondary'
                                    }>Risque {activite.niveau_risque}</Badge>
                                    {activite.est_jalon && <Badge variant="warning">◆ Jalon</Badge>}
                                </div>
                                <CardTitle className="text-xl">{activite.libelle}</CardTitle>
                                {activite.description && (
                                    <p className="mt-2 text-sm text-muted-foreground leading-relaxed">{activite.description}</p>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 border-t pt-4 md:grid-cols-4">
                        <Meta icon={ClipboardList} label="Action prioritaire" value={activite.action_prioritaire ? `${activite.action_prioritaire.code}` : '—'} sub={activite.action_prioritaire?.libelle} />
                        <Meta icon={Calendar} label="Période prévue" value={`${formatDate(activite.date_debut_prevue)} → ${formatDate(activite.date_fin_prevue)}`} />
                        <Meta icon={Calendar} label="Période réelle" value={activite.date_debut_reelle ? `${formatDate(activite.date_debut_reelle)} → ${formatDate(activite.date_fin_reelle)}` : 'Pas démarrée'} />
                        <Meta icon={User} label="Point focal" value={activite.point_focal?.name ?? '—'} sub={activite.point_focal?.fonction} />
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle className="text-base">Avancement</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <div className="flex justify-between text-sm mb-1">
                                    <span>Avancement physique</span>
                                    <span className="tabular-nums font-medium">{formatPercent(activite.avancement)}</span>
                                </div>
                                <Progress value={activite.avancement} indicatorClassName={activite.avancement >= 75 ? 'bg-success' : activite.avancement >= 40 ? 'bg-warning' : 'bg-destructive'} />
                            </div>
                            {can.updateAvancement && (
                                <form onSubmit={submit} className="space-y-3 border-t pt-4">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Mettre à jour</p>
                                    <div className="grid grid-cols-2 gap-2">
                                        <div className="space-y-1">
                                            <Label htmlFor="avancement">Avancement (%)</Label>
                                            <Input id="avancement" type="number" min={0} max={100} step={1} value={data.avancement} onChange={(e) => setData('avancement', Number(e.target.value))} />
                                            {errors.avancement && <p className="text-xs text-destructive">{errors.avancement}</p>}
                                        </div>
                                        <div className="space-y-1">
                                            <Label>Statut</Label>
                                            <Select value={data.statut} onValueChange={(v) => setData('statut', v)}>
                                                <SelectTrigger><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="planifiee">Planifiée</SelectItem>
                                                    <SelectItem value="en_cours">En cours</SelectItem>
                                                    <SelectItem value="realisee">Réalisée</SelectItem>
                                                    <SelectItem value="suspendue">Suspendue</SelectItem>
                                                    <SelectItem value="annulee">Annulée</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="commentaire">Commentaire (justification, contraintes)</Label>
                                        <Textarea id="commentaire" rows={2} value={data.commentaire} onChange={(e) => setData('commentaire', e.target.value)} />
                                    </div>
                                    <Button type="submit" disabled={processing} size="sm">
                                        {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                        Enregistrer l'avancement
                                    </Button>
                                </form>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-base">Responsabilités</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <Meta icon={User} label="Responsable hiérarchique" value={activite.responsable?.name ?? '—'} sub={activite.responsable?.fonction} />
                            <Meta icon={User} label="Direction" value={activite.direction ? `${activite.direction.code} — ${activite.direction.libelle}` : '—'} />
                            <Meta icon={ClipboardList} label="Résultat rattaché" value={activite.resultat_attendu?.libelle ?? '—'} />
                            <Meta icon={ClipboardList} label="PAPA" value={activite.action_prioritaire?.papa ? `${activite.action_prioritaire.papa.annee} — ${activite.action_prioritaire.papa.libelle}` : '—'} />
                        </CardContent>
                    </Card>
                </div>

                {activite.taches?.length > 0 && (
                    <Card>
                        <CardHeader><CardTitle className="text-base">Tâches ({activite.taches.length})</CardTitle></CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Libellé</TableHead>
                                        <TableHead>Période</TableHead>
                                        <TableHead>Statut</TableHead>
                                        <TableHead>Assigné à</TableHead>
                                        <TableHead className="w-32">Avancement</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {activite.taches.map((t: any) => (
                                        <TableRow key={t.id}>
                                            <TableCell>{t.libelle}</TableCell>
                                            <TableCell className="text-xs">{formatDate(t.date_debut_prevue)} → {formatDate(t.date_fin_prevue)}</TableCell>
                                            <TableCell><StatusBadge statut={t.statut} /></TableCell>
                                            <TableCell className="text-xs">{t.assigne_a?.name ?? '—'}</TableCell>
                                            <TableCell><Progress value={t.avancement} /></TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
                </div>
</AppLayout>
    );
}

function Meta({ icon: Icon, label, value, sub }: { icon: any; label: string; value: string; sub?: string | null }) {
    return (
        <div className="flex items-start gap-2">
            <Icon className="h-4 w-4 mt-0.5 text-muted-foreground" />
            <div className="min-w-0">
                <p className="text-xs text-muted-foreground">{label}</p>
                <p className="text-sm font-medium truncate">{value}</p>
                {sub && <p className="text-xs text-muted-foreground truncate">{sub}</p>}
            </div>
        </div>
    );
}
