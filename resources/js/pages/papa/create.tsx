import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    annee_suggeree: number;
}

export default function PapaCreate({ annee_suggeree }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        annee: annee_suggeree,
        version: '1.0',
        libelle: `Plan d'Action Prioritaire Annuel ${annee_suggeree}`,
        description: '',
        perimetre_institutionnel: '',
        date_debut: `${annee_suggeree}-01-01`,
        date_fin: `${annee_suggeree}-12-31`,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/papa');
    };

    return (
        <AppLayout
            pageTitle="Nouveau PAPA"
            breadcrumbs={[{ label: 'PAPA', href: '/papa' }, { label: 'Création' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Créer un PAPA"
                    description="Initialisation du référentiel stratégique annuel et de sa période de pilotage."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/papa">
                        <ArrowLeft className="h-4 w-4" />
                        Retour
                    </Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <CardTitle>Définition du Plan d'Action Prioritaire Annuel</CardTitle>
                    <CardDescription>
                        Le PAPA constitue l'entité racine du système. Il sera créé au statut "brouillon" puis soumis à validation.
                    </CardDescription>
                </CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="annee">Année *</Label>
                                <Input
                                    id="annee"
                                    type="number"
                                    min={2024}
                                    max={2050}
                                    value={data.annee}
                                    onChange={(e) => setData('annee', Number(e.target.value))}
                                    required
                                />
                                {errors.annee && <p className="text-xs text-destructive">{errors.annee}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="version">Version *</Label>
                                <Input
                                    id="version"
                                    value={data.version}
                                    onChange={(e) => setData('version', e.target.value)}
                                    required
                                />
                                {errors.version && <p className="text-xs text-destructive">{errors.version}</p>}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="libelle">Libellé *</Label>
                            <Input
                                id="libelle"
                                value={data.libelle}
                                onChange={(e) => setData('libelle', e.target.value)}
                                required
                            />
                            {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description générale</Label>
                            <Textarea
                                id="description"
                                rows={3}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="Présentation synthétique du PAPA, contexte, objectifs stratégiques…"
                            />
                            {errors.description && <p className="text-xs text-destructive">{errors.description}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="perimetre_institutionnel">Périmètre institutionnel couvert</Label>
                            <Textarea
                                id="perimetre_institutionnel"
                                rows={3}
                                value={data.perimetre_institutionnel}
                                onChange={(e) => setData('perimetre_institutionnel', e.target.value)}
                                placeholder="Présidence, Vice-Présidence, Départements et Directions concernés…"
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="date_debut">Date de début</Label>
                                <Input
                                    id="date_debut"
                                    type="date"
                                    value={data.date_debut}
                                    onChange={(e) => setData('date_debut', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_fin">Date de fin</Label>
                                <Input
                                    id="date_fin"
                                    type="date"
                                    value={data.date_fin}
                                    onChange={(e) => setData('date_fin', e.target.value)}
                                />
                                {errors.date_fin && <p className="text-xs text-destructive">{errors.date_fin}</p>}
                            </div>
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" asChild type="button">
                            <Link href="/papa">Annuler</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            Créer le PAPA
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
