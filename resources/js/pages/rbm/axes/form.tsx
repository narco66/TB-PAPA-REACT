import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    mode: 'create' | 'edit';
    axe?: any;
    papa_id_defaut?: number | null;
    papas: any[];
    departements: any[];
    utilisateurs: any[];
}

export default function AxeForm({ mode, axe, papa_id_defaut, papas, departements, utilisateurs }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        papa_id: axe?.papa_id ?? papa_id_defaut ?? '' as number | '',
        libelle: axe?.libelle ?? '',
        description: axe?.description ?? '',
        statut: axe?.statut ?? 'brouillon',
        poids: axe?.poids ?? 100,
        date_debut: axe?.date_debut ? String(axe.date_debut).substring(0, 10) : '',
        date_fin: axe?.date_fin ? String(axe.date_fin).substring(0, 10) : '',
        departement_id: axe?.departement_id ?? '' as number | '',
        responsable_id: axe?.responsable_id ?? '' as number | '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (mode === 'create') post('/rbm/axes');
        else put(`/rbm/axes/${axe.id}`);
    };

    return (
        <AppLayout
            pageTitle={mode === 'create' ? 'Nouvel Axe' : `Modifier ${axe?.code}`}
            breadcrumbs={[{ label: 'RBM' }, { label: 'Axes', href: '/rbm/axes' }, { label: mode === 'create' ? 'Création' : 'Édition' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Formulaire Axe"
                    description="Création et mise à jour des priorités stratégiques de la chaîne RBM/GAR."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/rbm/axes"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <CardTitle>{mode === 'create' ? 'Création d\'un Axe stratégique' : 'Modification de l\'Axe'}</CardTitle>
                    <CardDescription>
                        Le code (AXE 1, AXE 2, …) est généré automatiquement à partir de l'ordre.
                    </CardDescription>
                </CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>PAPA *</Label>
                                <Select value={String(data.papa_id)} onValueChange={(v) => setData('papa_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="Choisir un PAPA" /></SelectTrigger>
                                    <SelectContent>
                                        {papas.map((p) => (
                                            <SelectItem key={p.id} value={String(p.id)}>{p.annee} — {p.libelle}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.papa_id && <p className="text-xs text-destructive">{errors.papa_id}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label>Département technique</Label>
                                <Select value={String(data.departement_id)} onValueChange={(v) => setData('departement_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {departements.map((d) => (
                                            <SelectItem key={d.id} value={String(d.id)}>{d.code} — {d.libelle}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Libellé *</Label>
                            <Input value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                            {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label>Description</Label>
                            <Textarea rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Statut</Label>
                                <Select value={data.statut} onValueChange={(v) => setData('statut', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="brouillon">Brouillon</SelectItem>
                                        <SelectItem value="soumis">Soumis</SelectItem>
                                        <SelectItem value="en_validation">En validation</SelectItem>
                                        <SelectItem value="valide">Validé</SelectItem>
                                        <SelectItem value="rejete">Rejeté</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Poids (pondération)</Label>
                                <Input type="number" step="0.01" value={data.poids} onChange={(e) => setData('poids', Number(e.target.value))} />
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
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Date de début</Label>
                                <Input type="date" value={data.date_debut} onChange={(e) => setData('date_debut', e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label>Date de fin</Label>
                                <Input type="date" value={data.date_fin} onChange={(e) => setData('date_fin', e.target.value)} />
                            </div>
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/rbm/axes">Annuler</Link></Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            {mode === 'create' ? 'Créer l\'Axe' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
