import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileWarning, LoaderCircle, Plus, Users } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

export default function MissionShow({ mission, constats, can }: any) {
    const [showConstatForm, setShowConstatForm] = useState(false);
    const equipe = mission.equipe ?? [];
    const items = constats ?? [];

    const { data, setData, post, processing, errors, reset } = useForm({
        mission_id: mission.id,
        code: '',
        libelle: '',
        description: '',
        gravite: 'moyen',
        nature: 'non_conformite',
        preuves: '',
        cause_racine: '',
        impact: '',
    });

    const submitConstat = (e: FormEvent) => {
        e.preventDefault();
        post('/audit/constats', { onSuccess: () => { reset(); setShowConstatForm(false); } });
    };

    return (
        <AppLayout
            pageTitle={`Mission ${mission.code}`}
            breadcrumbs={[
                { label: 'Audit interne', href: '/audit' },
                { label: 'Missions', href: '/audit/missions' },
                { label: mission.code },
            ]}
            actions={can.constat_create && (
                <Button onClick={() => setShowConstatForm(!showConstatForm)}>
                    <Plus className="h-4 w-4" />{showConstatForm ? 'Annuler' : 'Nouveau constat'}
                </Button>
            )}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow={`${mission.code} — ${mission.statut}`}
                    title={mission.titre}
                    description={mission.objectifs ?? 'Mission d\'audit interne'}
                    metrics={[
                        { icon: FileWarning, label: 'Constats', value: String(items.length) },
                        { icon: Users, label: 'Équipe', value: String(equipe.length + (mission.chef_mission ? 1 : 0)) },
                    ]}
                />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/audit/missions"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Plan & périmètre</CardTitle></CardHeader>
                        <CardContent className="text-sm space-y-1">
                            <div><span className="text-muted-foreground">Plan :</span> {mission.plan?.annee} — {mission.plan?.libelle}</div>
                            <div><span className="text-muted-foreground">Type :</span> {mission.type}</div>
                            <div><span className="text-muted-foreground">Priorité :</span> {mission.priorite}</div>
                            <div><span className="text-muted-foreground">Département :</span> {mission.departement_audite?.libelle ?? '—'}</div>
                            <div><span className="text-muted-foreground">Direction :</span> {mission.direction_auditee?.libelle ?? '—'}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Calendrier</CardTitle></CardHeader>
                        <CardContent className="text-sm space-y-1">
                            <div><span className="text-muted-foreground">Début prévu :</span> {mission.date_debut_prevue ? new Date(mission.date_debut_prevue).toLocaleDateString('fr-FR') : '—'}</div>
                            <div><span className="text-muted-foreground">Fin prévue :</span> {mission.date_fin_prevue ? new Date(mission.date_fin_prevue).toLocaleDateString('fr-FR') : '—'}</div>
                            <div><span className="text-muted-foreground">Début réel :</span> {mission.date_debut_reelle ? new Date(mission.date_debut_reelle).toLocaleDateString('fr-FR') : '—'}</div>
                            <div><span className="text-muted-foreground">Fin réelle :</span> {mission.date_fin_reelle ? new Date(mission.date_fin_reelle).toLocaleDateString('fr-FR') : '—'}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Équipe</CardTitle></CardHeader>
                        <CardContent className="text-sm space-y-1">
                            <div><span className="text-muted-foreground">Chef :</span> {mission.chef_mission?.name ?? '—'}</div>
                            {equipe.length === 0 ? (
                                <div className="text-muted-foreground italic">Aucun membre d'équipe</div>
                            ) : equipe.map((u: any) => (
                                <div key={u.id}>• {u.name} <span className="text-xs text-muted-foreground">({u.pivot?.role_mission})</span></div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                {mission.lettre_mission && (
                    <Card>
                        <CardHeader><CardTitle>Lettre de mission</CardTitle></CardHeader>
                        <CardContent><p className="whitespace-pre-line text-sm">{mission.lettre_mission}</p></CardContent>
                    </Card>
                )}

                {showConstatForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouveau constat</CardTitle></CardHeader>
                        <form onSubmit={submitConstat}>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Code *</Label>
                                        <Input value={data.code} onChange={(e) => setData('code', e.target.value)} placeholder="C-001" required />
                                        {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Gravité *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.gravite} onChange={(e) => setData('gravite', e.target.value)}>
                                            {['critique', 'majeur', 'moyen', 'mineur', 'observation'].map(g => <option key={g} value={g}>{g}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Nature *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.nature} onChange={(e) => setData('nature', e.target.value)}>
                                            {['non_conformite', 'risque', 'inefficacite', 'inefficience', 'controle_insuffisant', 'bonne_pratique', 'autre'].map(n => <option key={n} value={n}>{n}</option>)}
                                        </select>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Libellé *</Label>
                                    <Input value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                                    {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>Description *</Label>
                                    <Textarea rows={4} value={data.description} onChange={(e) => setData('description', e.target.value)} required />
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Cause racine</Label>
                                        <Textarea rows={3} value={data.cause_racine} onChange={(e) => setData('cause_racine', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Impact</Label>
                                        <Textarea rows={3} value={data.impact} onChange={(e) => setData('impact', e.target.value)} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Preuves</Label>
                                    <Textarea rows={2} value={data.preuves} onChange={(e) => setData('preuves', e.target.value)} placeholder="Pièces justificatives, références GED" />
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button variant="outline" type="button" onClick={() => setShowConstatForm(false)}>Annuler</Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Enregistrer le constat
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                <Card>
                    <CardHeader><CardTitle>Constats ({items.length})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Gravité</TableHead>
                                    <TableHead>Nature</TableHead>
                                    <TableHead className="text-right">Recos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={5} className="py-8 text-center text-muted-foreground">Aucun constat enregistré.</TableCell></TableRow>
                                ) : items.map((c: any) => (
                                    <TableRow key={c.id}>
                                        <TableCell className="font-mono text-xs">{c.code}</TableCell>
                                        <TableCell><Link href={`/audit/constats/${c.id}`} className="font-medium hover:underline">{c.libelle}</Link></TableCell>
                                        <TableCell className="text-xs">{c.gravite}</TableCell>
                                        <TableCell className="text-xs">{c.nature}</TableCell>
                                        <TableCell className="text-right tabular-nums">{c.recommandations_count}</TableCell>
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
