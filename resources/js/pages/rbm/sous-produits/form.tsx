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

export default function SousProduitForm({ mode, sousProduit, produit_id_defaut, produits, directions, utilisateurs }: any) {
    const { data, setData, post, put, processing, errors } = useForm({
        produit_id: sousProduit?.produit_id ?? produit_id_defaut ?? '',
        libelle: sousProduit?.libelle ?? '',
        description: sousProduit?.description ?? '',
        statut: sousProduit?.statut ?? 'brouillon',
        poids: sousProduit?.poids ?? 100,
        date_debut: sousProduit?.date_debut ? String(sousProduit.date_debut).substring(0, 10) : '',
        date_fin: sousProduit?.date_fin ? String(sousProduit.date_fin).substring(0, 10) : '',
        direction_id: sousProduit?.direction_id ?? '',
        responsable_id: sousProduit?.responsable_id ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (mode === 'create') post('/rbm/sous-produits');
        else put(`/rbm/sous-produits/${sousProduit.id}`);
    };

    return (
        <AppLayout
            pageTitle={mode === 'create' ? 'Nouveau Sous-Produit' : `Modifier ${sousProduit?.code}`}
            breadcrumbs={[{ label: 'RBM' }, { label: 'Sous-Produits', href: '/rbm/sous-produits' }, { label: mode === 'create' ? 'Création' : 'Édition' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Formulaire Sous-Produit"
                    description="Création et mise à jour des résultats intermédiaires de la chaîne RBM/GAR."
                />
            <div className="mb-4"><Button variant="ghost" size="sm" asChild><Link href="/rbm/sous-produits"><ArrowLeft className="h-4 w-4" />Retour</Link></Button></div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader><CardTitle>{mode === 'create' ? 'Création d\'un Sous-Produit' : 'Modifier le Sous-Produit'}</CardTitle></CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label>Produit parent *</Label>
                            <Select value={String(data.produit_id)} onValueChange={(v) => setData('produit_id', Number(v))}>
                                <SelectTrigger><SelectValue placeholder="Choisir un produit" /></SelectTrigger>
                                <SelectContent>
                                    {produits.map((p: any) => (<SelectItem key={p.id} value={String(p.id)}>{p.code} — {p.libelle}</SelectItem>))}
                                </SelectContent>
                            </Select>
                            {errors.produit_id && <p className="text-xs text-destructive">{errors.produit_id}</p>}
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
                                <Label>Direction</Label>
                                <Select value={String(data.direction_id)} onValueChange={(v) => setData('direction_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                    <SelectContent>
                                        {directions.map((d: any) => (<SelectItem key={d.id} value={String(d.id)}>{d.code}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2"><Label>Poids</Label><Input type="number" step="0.01" value={data.poids} onChange={(e) => setData('poids', Number(e.target.value))} /></div>
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
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2"><Label>Date début</Label><Input type="date" value={data.date_debut} onChange={(e) => setData('date_debut', e.target.value)} /></div>
                            <div className="space-y-2"><Label>Date fin</Label><Input type="date" value={data.date_fin} onChange={(e) => setData('date_fin', e.target.value)} /></div>
                        </div>
                        <div className="space-y-2">
                            <Label>Responsable</Label>
                            <Select value={String(data.responsable_id)} onValueChange={(v) => setData('responsable_id', Number(v))}>
                                <SelectTrigger><SelectValue placeholder="—" /></SelectTrigger>
                                <SelectContent>
                                    {utilisateurs.map((u: any) => (<SelectItem key={u.id} value={String(u.id)}>{u.name}</SelectItem>))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/rbm/sous-produits">Annuler</Link></Button>
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
