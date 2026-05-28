import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, Plus } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

export default function ConstatShow({ constat, recommandations, can }: any) {
    const [showRecoForm, setShowRecoForm] = useState(false);
    const items = recommandations ?? [];

    const { data, setData, post, processing, errors, reset } = useForm({
        constat_id: constat.id,
        code: '',
        libelle: '',
        action_proposee: '',
        priorite: 'moyenne',
        date_echeance: '',
        statut: 'ouverte',
    });

    const submitReco = (e: FormEvent) => {
        e.preventDefault();
        post('/audit/recommandations', { onSuccess: () => { reset(); setShowRecoForm(false); } });
    };

    return (
        <AppLayout
            pageTitle={`Constat ${constat.code}`}
            breadcrumbs={[
                { label: 'Audit interne', href: '/audit' },
                { label: 'Missions', href: '/audit/missions' },
                { label: constat.mission?.code, href: `/audit/missions/${constat.mission?.id}` },
                { label: constat.code },
            ]}
            actions={can.recommandation_create && (
                <Button onClick={() => setShowRecoForm(!showRecoForm)}>
                    <Plus className="h-4 w-4" />{showRecoForm ? 'Annuler' : 'Nouvelle recommandation'}
                </Button>
            )}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow={`${constat.code} — Gravité : ${constat.gravite}`}
                    title={constat.libelle}
                    description={constat.description}
                />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/audit/missions/${constat.mission?.id}`}><ArrowLeft className="h-4 w-4" />Retour mission</Link></Button>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Cause racine</CardTitle></CardHeader>
                        <CardContent className="text-sm whitespace-pre-line">{constat.cause_racine ?? <span className="text-muted-foreground italic">Non renseigné</span>}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Impact</CardTitle></CardHeader>
                        <CardContent className="text-sm whitespace-pre-line">{constat.impact ?? <span className="text-muted-foreground italic">Non renseigné</span>}</CardContent>
                    </Card>
                </div>

                {constat.preuves && (
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Preuves</CardTitle></CardHeader>
                        <CardContent className="text-sm whitespace-pre-line">{constat.preuves}</CardContent>
                    </Card>
                )}

                {showRecoForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouvelle recommandation</CardTitle></CardHeader>
                        <form onSubmit={submitReco}>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Code *</Label>
                                        <Input value={data.code} onChange={(e) => setData('code', e.target.value)} placeholder="R-001" required />
                                        {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Priorité *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.priorite} onChange={(e) => setData('priorite', e.target.value)}>
                                            {['urgente', 'haute', 'moyenne', 'basse'].map(p => <option key={p} value={p}>{p}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Date d'échéance</Label>
                                        <Input type="date" value={data.date_echeance} onChange={(e) => setData('date_echeance', e.target.value)} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Libellé *</Label>
                                    <Input value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                                </div>
                                <div className="space-y-2">
                                    <Label>Action proposée *</Label>
                                    <Textarea rows={4} value={data.action_proposee} onChange={(e) => setData('action_proposee', e.target.value)} required />
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button variant="outline" type="button" onClick={() => setShowRecoForm(false)}>Annuler</Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Enregistrer
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                <Card>
                    <CardHeader><CardTitle>Recommandations ({items.length})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Priorité</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Échéance</TableHead>
                                    <TableHead>Responsable</TableHead>
                                    <TableHead className="text-right">%</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground">Aucune recommandation.</TableCell></TableRow>
                                ) : items.map((r: any) => (
                                    <TableRow key={r.id}>
                                        <TableCell className="font-mono text-xs">{r.code}</TableCell>
                                        <TableCell><Link href={`/audit/recommandations/${r.id}`} className="font-medium hover:underline">{r.libelle}</Link></TableCell>
                                        <TableCell className="text-xs">{r.priorite}</TableCell>
                                        <TableCell className="text-xs">{r.statut}</TableCell>
                                        <TableCell className="text-xs">{r.date_echeance ? new Date(r.date_echeance).toLocaleDateString('fr-FR') : '—'}</TableCell>
                                        <TableCell className="text-xs">{r.responsable?.name ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums">{r.pourcentage_avancement}%</TableCell>
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
