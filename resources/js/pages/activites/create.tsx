import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    actions: Array<{ id: number; code: string; libelle: string; papa: number | null }>;
    resultats: Array<{ id: number; code: string; libelle: string }>;
    directions: Array<{ id: number; code: string; libelle: string }>;
    utilisateurs: Array<{ id: number; name: string; fonction: string | null }>;
}

export default function ActiviteCreate({ actions, resultats, directions, utilisateurs }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        action_prioritaire_id: '' as number | '',
        resultat_attendu_id: '' as number | '',
        code: '',
        libelle: '',
        description: '',
        date_debut_prevue: '',
        date_fin_prevue: '',
        direction_id: '' as number | '',
        responsable_id: '' as number | '',
        point_focal_id: '' as number | '',
        niveau_risque: 'faible' as 'faible' | 'moyen' | 'eleve' | 'critique',
        est_jalon: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/activites');
    };

    return (
        <AppLayout
            pageTitle="Nouvelle activité"
            breadcrumbs={[{ label: 'Activités', href: '/activites' }, { label: 'Création' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Créer une activité"
                    description="Saisie d'une activité planifiée, de ses échéances, responsabilités et risques."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/activites"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader><CardTitle>Définir une activité planifiée</CardTitle></CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Action prioritaire *</Label>
                                <Select value={String(data.action_prioritaire_id)} onValueChange={(v) => setData('action_prioritaire_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="Choisir une action" /></SelectTrigger>
                                    <SelectContent>
                                        {actions.map((a) => (
                                            <SelectItem key={a.id} value={String(a.id)}>{a.code} — {a.libelle}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.action_prioritaire_id && <p className="text-xs text-destructive">{errors.action_prioritaire_id}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="code">Code *</Label>
                                <Input id="code" value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} required placeholder="ACT-001" />
                                {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="libelle">Libellé *</Label>
                            <Input id="libelle" value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea id="description" rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="date_debut_prevue">Date début prévue *</Label>
                                <Input id="date_debut_prevue" type="date" value={data.date_debut_prevue} onChange={(e) => setData('date_debut_prevue', e.target.value)} required />
                                {errors.date_debut_prevue && <p className="text-xs text-destructive">{errors.date_debut_prevue}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_fin_prevue">Date fin prévue *</Label>
                                <Input id="date_fin_prevue" type="date" value={data.date_fin_prevue} onChange={(e) => setData('date_fin_prevue', e.target.value)} required />
                                {errors.date_fin_prevue && <p className="text-xs text-destructive">{errors.date_fin_prevue}</p>}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Direction porteuse</Label>
                                <Select value={String(data.direction_id)} onValueChange={(v) => setData('direction_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {directions.map((d) => (<SelectItem key={d.id} value={String(d.id)}>{d.code}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Responsable</Label>
                                <Select value={String(data.responsable_id)} onValueChange={(v) => setData('responsable_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {utilisateurs.map((u) => (<SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Point focal</Label>
                                <Select value={String(data.point_focal_id)} onValueChange={(v) => setData('point_focal_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {utilisateurs.map((u) => (<SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Résultat attendu rattaché</Label>
                                <Select value={String(data.resultat_attendu_id)} onValueChange={(v) => setData('resultat_attendu_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {resultats.map((r) => (<SelectItem key={r.id} value={String(r.id)}>{r.code} — {r.libelle}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Niveau de risque</Label>
                                <Select value={data.niveau_risque} onValueChange={(v) => setData('niveau_risque', v as any)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="faible">Faible</SelectItem>
                                        <SelectItem value="moyen">Moyen</SelectItem>
                                        <SelectItem value="eleve">Élevé</SelectItem>
                                        <SelectItem value="critique">Critique</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <label className="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" checked={data.est_jalon} onChange={(e) => setData('est_jalon', e.target.checked)} className="h-4 w-4" />
                            Cette activité est un jalon clé (milestone)
                        </label>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/activites">Annuler</Link></Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            Créer l'activité
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
