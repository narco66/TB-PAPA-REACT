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
import type { Papa } from '@/types';

export default function PapaEdit({ papa }: { papa: Papa }) {
    const { data, setData, put, processing, errors } = useForm({
        annee: papa.annee,
        version: papa.version,
        libelle: papa.libelle,
        description: papa.description ?? '',
        perimetre_institutionnel: (papa as any).perimetre_institutionnel ?? '',
        date_debut: (papa as any).date_debut ?? '',
        date_fin: (papa as any).date_fin ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/papa/${papa.id}`);
    };

    return (
        <AppLayout
            pageTitle={`Modifier PAPA ${papa.annee}`}
            breadcrumbs={[{ label: 'PAPA', href: '/papa' }, { label: `${papa.annee}`, href: `/papa/${papa.id}` }, { label: 'Édition' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Modifier un PAPA"
                    description="Mise à jour du référentiel stratégique annuel et de ses paramètres."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={`/papa/${papa.id}`}>
                        <ArrowLeft className="h-4 w-4" />
                        Retour
                    </Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <CardTitle>Modifier le PAPA</CardTitle>
                    <CardDescription>Toute modification sera tracée dans le journal d'audit.</CardDescription>
                </CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="annee">Année</Label>
                                <Input
                                    id="annee"
                                    type="number"
                                    value={data.annee}
                                    onChange={(e) => setData('annee', Number(e.target.value))}
                                />
                                {errors.annee && <p className="text-xs text-destructive">{errors.annee}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="version">Version</Label>
                                <Input
                                    id="version"
                                    value={data.version}
                                    onChange={(e) => setData('version', e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="libelle">Libellé</Label>
                            <Input id="libelle" value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea id="description" rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="perimetre">Périmètre institutionnel</Label>
                            <Textarea id="perimetre" rows={3} value={data.perimetre_institutionnel} onChange={(e) => setData('perimetre_institutionnel', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="date_debut">Date de début</Label>
                                <Input
                                    id="date_debut"
                                    type="date"
                                    value={data.date_debut ? String(data.date_debut).substring(0, 10) : ''}
                                    onChange={(e) => setData('date_debut', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="date_fin">Date de fin</Label>
                                <Input
                                    id="date_fin"
                                    type="date"
                                    value={data.date_fin ? String(data.date_fin).substring(0, 10) : ''}
                                    onChange={(e) => setData('date_fin', e.target.value)}
                                />
                            </div>
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild>
                            <Link href={`/papa/${papa.id}`}>Annuler</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            Enregistrer
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
