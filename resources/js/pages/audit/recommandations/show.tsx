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

export default function RecommandationShow({ recommandation, suivis, etats, can }: any) {
    const [showSuiviForm, setShowSuiviForm] = useState(false);
    const items = suivis ?? [];

    const { data, setData, post, processing, errors, reset } = useForm({
        recommandation_id: recommandation.id,
        date_suivi: new Date().toISOString().substring(0, 10),
        etat_avancement: 'en_cours',
        pourcentage: recommandation.pourcentage_avancement ?? 0,
        actions_realisees: '',
        actions_restantes: '',
        blocages: '',
        commentaire: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/audit/suivis', { onSuccess: () => { reset(); setShowSuiviForm(false); } });
    };

    return (
        <AppLayout
            pageTitle={`Recommandation ${recommandation.code}`}
            breadcrumbs={[
                { label: 'Audit interne', href: '/audit' },
                { label: 'Recommandations', href: '/audit/recommandations' },
                { label: recommandation.code },
            ]}
            actions={can.suivi_create && (
                <Button onClick={() => setShowSuiviForm(!showSuiviForm)}>
                    <Plus className="h-4 w-4" />{showSuiviForm ? 'Annuler' : 'Nouveau suivi'}
                </Button>
            )}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow={`${recommandation.code} — Priorité : ${recommandation.priorite} — ${recommandation.pourcentage_avancement}%`}
                    title={recommandation.libelle}
                    description={recommandation.action_proposee}
                />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/audit/constats/${recommandation.constat?.id}`}><ArrowLeft className="h-4 w-4" />Retour constat</Link>
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Statut</CardTitle></CardHeader>
                        <CardContent className="text-sm">{recommandation.statut}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Échéance</CardTitle></CardHeader>
                        <CardContent className="text-sm">{recommandation.date_echeance ? new Date(recommandation.date_echeance).toLocaleDateString('fr-FR') : '—'}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Responsable</CardTitle></CardHeader>
                        <CardContent className="text-sm">{recommandation.responsable?.name ?? '—'} <span className="text-xs text-muted-foreground">{recommandation.responsable?.fonction}</span></CardContent>
                    </Card>
                </div>

                {showSuiviForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouveau suivi</CardTitle></CardHeader>
                        <form onSubmit={submit}>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Date *</Label>
                                        <Input type="date" value={data.date_suivi} onChange={(e) => setData('date_suivi', e.target.value)} required />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>État *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.etat_avancement} onChange={(e) => setData('etat_avancement', e.target.value)}>
                                            {etats.map((e: string) => <option key={e} value={e}>{e}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Avancement (%) *</Label>
                                        <Input type="number" min={0} max={100} value={data.pourcentage} onChange={(e) => setData('pourcentage', Number(e.target.value))} required />
                                        {errors.pourcentage && <p className="text-xs text-destructive">{errors.pourcentage}</p>}
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Actions réalisées</Label>
                                        <Textarea rows={3} value={data.actions_realisees} onChange={(e) => setData('actions_realisees', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Actions restantes</Label>
                                        <Textarea rows={3} value={data.actions_restantes} onChange={(e) => setData('actions_restantes', e.target.value)} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Blocages</Label>
                                    <Textarea rows={2} value={data.blocages} onChange={(e) => setData('blocages', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Commentaire</Label>
                                    <Textarea rows={2} value={data.commentaire} onChange={(e) => setData('commentaire', e.target.value)} />
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button variant="outline" type="button" onClick={() => setShowSuiviForm(false)}>Annuler</Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Enregistrer le suivi
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                <Card>
                    <CardHeader><CardTitle>Historique des suivis ({items.length})</CardTitle></CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>État</TableHead>
                                    <TableHead className="text-right">%</TableHead>
                                    <TableHead>Actions réalisées</TableHead>
                                    <TableHead>Blocages</TableHead>
                                    <TableHead>Par</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={6} className="py-8 text-center text-muted-foreground">Aucun suivi enregistré.</TableCell></TableRow>
                                ) : items.map((s: any) => (
                                    <TableRow key={s.id}>
                                        <TableCell className="text-xs">{new Date(s.date_suivi).toLocaleDateString('fr-FR')}</TableCell>
                                        <TableCell className="text-xs">{s.etat_avancement}</TableCell>
                                        <TableCell className="text-right tabular-nums">{s.pourcentage}%</TableCell>
                                        <TableCell className="text-xs max-w-xs truncate">{s.actions_realisees ?? '—'}</TableCell>
                                        <TableCell className="text-xs max-w-xs truncate">{s.blocages ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{s.suivi_par?.name ?? '—'}</TableCell>
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
