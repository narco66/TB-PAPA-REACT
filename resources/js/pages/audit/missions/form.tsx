import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export default function MissionForm({ plans, plan_id_initial, departements, directions, auditeurs, types, priorites, statuts }: any) {
    const annee = new Date().getFullYear();
    const { data, setData, post, processing, errors } = useForm<any>({
        plan_id: plan_id_initial ?? plans[0]?.id ?? '',
        code: `M-${annee}-`,
        titre: '',
        objectifs: '',
        perimetre: '',
        type: 'conformite',
        priorite: 'moyenne',
        statut: 'planifiee',
        date_debut_prevue: '',
        date_fin_prevue: '',
        chef_mission_id: '',
        departement_audite_id: '',
        direction_auditee_id: '',
        lettre_mission: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/audit/missions');
    };

    return (
        <AppLayout
            pageTitle="Nouvelle mission d'audit"
            breadcrumbs={[{ label: 'Audit interne', href: '/audit' }, { label: 'Missions', href: '/audit/missions' }, { label: 'Création' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero title="Mission d'audit interne" description="Lettre de mission, objectifs, périmètre, calendrier (IIA Standard 2200)." />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/audit/missions"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                </div>

                <Card className="mx-auto max-w-4xl">
                    <CardHeader><CardTitle>Création d'une mission</CardTitle></CardHeader>
                    <form onSubmit={submit}>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Plan d'audit *</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.plan_id} onChange={(e) => setData('plan_id', Number(e.target.value))} required>
                                        <option value="">— Choisir —</option>
                                        {plans.map((p: any) => <option key={p.id} value={p.id}>{p.annee} — {p.libelle}</option>)}
                                    </select>
                                    {errors.plan_id && <p className="text-xs text-destructive">{errors.plan_id}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>Code mission *</Label>
                                    <Input value={data.code} onChange={(e) => setData('code', e.target.value)} required placeholder={`M-${annee}-001`} />
                                    {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Titre *</Label>
                                <Input value={data.titre} onChange={(e) => setData('titre', e.target.value)} required />
                                {errors.titre && <p className="text-xs text-destructive">{errors.titre}</p>}
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label>Type *</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                                        {types.map((t: string) => <option key={t} value={t}>{t}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Priorité *</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.priorite} onChange={(e) => setData('priorite', e.target.value)}>
                                        {priorites.map((p: string) => <option key={p} value={p}>{p}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Statut</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.statut} onChange={(e) => setData('statut', e.target.value)}>
                                        {statuts.map((s: string) => <option key={s} value={s}>{s}</option>)}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label>Chef de mission</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.chef_mission_id} onChange={(e) => setData('chef_mission_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {auditeurs.map((u: any) => <option key={u.id} value={u.id}>{u.name}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Département audité</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.departement_audite_id} onChange={(e) => setData('departement_audite_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {departements.map((d: any) => <option key={d.id} value={d.id}>{d.libelle}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Direction auditée</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.direction_auditee_id} onChange={(e) => setData('direction_auditee_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {directions.map((d: any) => <option key={d.id} value={d.id}>{d.libelle}</option>)}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Date début prévue</Label>
                                    <Input type="date" value={data.date_debut_prevue} onChange={(e) => setData('date_debut_prevue', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Date fin prévue</Label>
                                    <Input type="date" value={data.date_fin_prevue} onChange={(e) => setData('date_fin_prevue', e.target.value)} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Objectifs</Label>
                                <Textarea rows={3} value={data.objectifs} onChange={(e) => setData('objectifs', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Périmètre</Label>
                                <Textarea rows={3} value={data.perimetre} onChange={(e) => setData('perimetre', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Lettre de mission</Label>
                                <Textarea rows={4} value={data.lettre_mission} onChange={(e) => setData('lettre_mission', e.target.value)} placeholder="Texte de la lettre de mission ou référence GED" />
                            </div>
                        </CardContent>
                        <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                            <Button variant="outline" type="button" asChild><Link href="/audit/missions">Annuler</Link></Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                Créer la mission
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
