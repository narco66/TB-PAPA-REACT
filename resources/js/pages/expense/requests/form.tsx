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

export default function ExpenseRequestForm({ exercices, departements, directions, activites, taches, sources, suppliers, types_engagement }: any) {
    const exerciceActif = exercices.find((e: any) => e.statut === 'valide') ?? exercices[0];

    const { data, setData, post, processing, errors } = useForm<any>({
        exercice_id: exerciceActif?.id ?? '',
        departement_id: '',
        direction_id: '',
        activite_id: '',
        tache_id: '',
        type_engagement: 'achat_biens',
        objet: '',
        justification: '',
        description_detaillee: '',
        montant_estime: 0,
        montant_estime_ceeac: 0,
        montant_estime_ptf: 0,
        devise: 'XAF',
        source_financement_id: '',
        supplier_pressenti_id: '',
        date_besoin_prevu: '',
        date_livraison_souhaitee: '',
        priorite: 3,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/expense/requests');
    };

    return (
        <AppLayout
            pageTitle="Nouvelle expression du besoin"
            breadcrumbs={[
                { label: 'Chaîne de la dépense', href: '/expense/requests' },
                { label: 'Expressions du besoin', href: '/expense/requests' },
                { label: 'Création' },
            ]}
        >
            <div className="space-y-6">
                <InstitutionalHero title="Expression du besoin" description="Initiation institutionnelle de la dépense (étape 1 du cycle RGCP)." />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/expense/requests"><ArrowLeft className="h-4 w-4" />Retour</Link>
                    </Button>
                </div>

                <Card className="mx-auto max-w-4xl">
                    <CardHeader><CardTitle>Création d'une expression du besoin</CardTitle></CardHeader>
                    <form onSubmit={submit}>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label>Exercice *</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.exercice_id} onChange={(e) => setData('exercice_id', Number(e.target.value))} required>
                                        {exercices.map((ex: any) => <option key={ex.id} value={ex.id}>{ex.annee} — {ex.libelle}</option>)}
                                    </select>
                                    {errors.exercice_id && <p className="text-xs text-destructive">{errors.exercice_id}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>Type d'engagement *</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.type_engagement} onChange={(e) => setData('type_engagement', e.target.value)} required>
                                        {Object.entries(types_engagement).map(([k, v]) => <option key={k} value={k}>{v as string}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Priorité</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.priorite} onChange={(e) => setData('priorite', Number(e.target.value))}>
                                        <option value={1}>1 — Urgente</option>
                                        <option value={2}>2 — Haute</option>
                                        <option value={3}>3 — Normale</option>
                                        <option value={4}>4 — Basse</option>
                                    </select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Objet *</Label>
                                <Input value={data.objet} onChange={(e) => setData('objet', e.target.value)} required />
                                {errors.objet && <p className="text-xs text-destructive">{errors.objet}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>Justification *</Label>
                                <Textarea rows={4} value={data.justification} onChange={(e) => setData('justification', e.target.value)} required />
                                {errors.justification && <p className="text-xs text-destructive">{errors.justification}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>Description détaillée</Label>
                                <Textarea rows={3} value={data.description_detaillee} onChange={(e) => setData('description_detaillee', e.target.value)} />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Département</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.departement_id} onChange={(e) => setData('departement_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {departements.map((d: any) => <option key={d.id} value={d.id}>{d.code} — {d.libelle}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Direction</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.direction_id} onChange={(e) => setData('direction_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {directions.map((d: any) => <option key={d.id} value={d.id}>{d.code} — {d.libelle}</option>)}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Activité (RBM)</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.activite_id} onChange={(e) => setData('activite_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {activites.map((a: any) => <option key={a.id} value={a.id}>{a.code} — {a.libelle}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Tâche (RBM)</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.tache_id} onChange={(e) => setData('tache_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {taches.map((t: any) => <option key={t.id} value={t.id}>{t.code} — {t.libelle}</option>)}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
                                <div className="space-y-2">
                                    <Label>Montant estimé *</Label>
                                    <Input type="number" step="0.01" min="0" value={data.montant_estime} onChange={(e) => setData('montant_estime', Number(e.target.value))} required />
                                </div>
                                <div className="space-y-2">
                                    <Label>Part CEEAC</Label>
                                    <Input type="number" step="0.01" min="0" value={data.montant_estime_ceeac} onChange={(e) => setData('montant_estime_ceeac', Number(e.target.value))} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Part PTF</Label>
                                    <Input type="number" step="0.01" min="0" value={data.montant_estime_ptf} onChange={(e) => setData('montant_estime_ptf', Number(e.target.value))} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Devise</Label>
                                    <Input value={data.devise} onChange={(e) => setData('devise', e.target.value)} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Source de financement</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.source_financement_id} onChange={(e) => setData('source_financement_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {sources.map((s: any) => <option key={s.id} value={s.id}>{s.code} — {s.libelle}</option>)}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Fournisseur pressenti</Label>
                                    <select className="w-full rounded-md border px-3 py-2 text-sm" value={data.supplier_pressenti_id} onChange={(e) => setData('supplier_pressenti_id', Number(e.target.value) || '')}>
                                        <option value="">—</option>
                                        {suppliers.map((s: any) => <option key={s.id} value={s.id}>{s.code} — {s.libelle}</option>)}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Date besoin prévu</Label>
                                    <Input type="date" value={data.date_besoin_prevu} onChange={(e) => setData('date_besoin_prevu', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Date livraison souhaitée</Label>
                                    <Input type="date" value={data.date_livraison_souhaitee} onChange={(e) => setData('date_livraison_souhaitee', e.target.value)} />
                                </div>
                            </div>
                        </CardContent>
                        <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                            <Button variant="outline" type="button" asChild><Link href="/expense/requests">Annuler</Link></Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                Créer (brouillon)
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AppLayout>
    );
}
