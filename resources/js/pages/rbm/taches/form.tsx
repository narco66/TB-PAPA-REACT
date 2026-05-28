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

export default function TacheForm({ mode, tache, activite_id_defaut, activites, utilisateurs }: any) {
    const { data, setData, post, put, processing, errors } = useForm({
        activite_id: tache?.activite_id ?? activite_id_defaut ?? '',
        libelle: tache?.libelle ?? '',
        description: tache?.description ?? '',
        statut: tache?.statut ?? 'planifiee',
        poids: tache?.poids ?? 100,
        taux_execution: tache?.taux_execution ?? 0,
        date_debut: tache?.date_debut ? String(tache.date_debut).substring(0, 10) : '',
        date_fin: tache?.date_fin ? String(tache.date_fin).substring(0, 10) : '',
        responsable_id: tache?.responsable_id ?? '',
        assigne_a_id: tache?.assigne_a_id ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (mode === 'create') post('/rbm/taches');
        else put(`/rbm/taches/${tache.id}`);
    };

    return (
        <AppLayout
            pageTitle={mode === 'create' ? 'Nouvelle Tâche' : `Modifier ${tache?.code}`}
            breadcrumbs={[{ label: 'RBM' }, { label: 'Tâches', href: '/rbm/taches' }, { label: mode === 'create' ? 'Création' : 'Édition' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Formulaire Tâche"
                    description="Création et mise à jour des tâches opérationnelles."
                />
            <div className="mb-4"><Button variant="ghost" size="sm" asChild><Link href="/rbm/taches"><ArrowLeft className="h-4 w-4" />Retour</Link></Button></div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader><CardTitle>{mode === 'create' ? 'Création d\'une Tâche' : 'Modifier la Tâche'}</CardTitle></CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label>Activité parente *</Label>
                            <Select value={String(data.activite_id)} onValueChange={(v) => setData('activite_id', Number(v))}>
                                <SelectTrigger><SelectValue placeholder="Choisir une activité" /></SelectTrigger>
                                <SelectContent>
                                    {activites.map((a: any) => (<SelectItem key={a.id} value={String(a.id)}>{a.code} — {a.libelle}</SelectItem>))}
                                </SelectContent>
                            </Select>
                            {errors.activite_id && <p className="text-xs text-destructive">{errors.activite_id}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Libellé *</Label>
                            <Input value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                            {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Description</Label>
                            <Textarea rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Statut</Label>
                                <Select value={data.statut} onValueChange={(v) => setData('statut', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="planifiee">Planifiée</SelectItem>
                                        <SelectItem value="en_cours">En cours</SelectItem>
                                        <SelectItem value="realisee">Réalisée</SelectItem>
                                        <SelectItem value="suspendue">Suspendue</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2"><Label>Poids</Label><Input type="number" step="0.01" value={data.poids} onChange={(e) => setData('poids', Number(e.target.value))} /></div>
                            <div className="space-y-2"><Label>Taux d'exécution (%)</Label><Input type="number" min={0} max={100} value={data.taux_execution} onChange={(e) => setData('taux_execution', Number(e.target.value))} /></div>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2"><Label>Date début</Label><Input type="date" value={data.date_debut} onChange={(e) => setData('date_debut', e.target.value)} /></div>
                            <div className="space-y-2"><Label>Date fin</Label><Input type="date" value={data.date_fin} onChange={(e) => setData('date_fin', e.target.value)} /></div>
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Responsable hiérarchique</Label>
                                <Select value={String(data.responsable_id)} onValueChange={(v) => setData('responsable_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {utilisateurs.map((u: any) => (<SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Assigné à</Label>
                                <Select value={String(data.assigne_a_id)} onValueChange={(v) => setData('assigne_a_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {utilisateurs.map((u: any) => (<SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/rbm/taches">Annuler</Link></Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            {mode === 'create' ? 'Créer' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
