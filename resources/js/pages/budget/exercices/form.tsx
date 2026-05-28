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

export default function ExerciceForm({ mode, exercice, annee_suggeree }: any) {
    const { data, setData, post, put, processing, errors } = useForm({
        annee: exercice?.annee ?? annee_suggeree ?? new Date().getFullYear() + 1,
        libelle: exercice?.libelle ?? `Budget de l'exercice ${annee_suggeree ?? new Date().getFullYear() + 1}`,
        description: exercice?.description ?? '',
        devise: exercice?.devise ?? 'XAF',
        date_debut: exercice?.date_debut ? String(exercice.date_debut).substring(0, 10) : `${annee_suggeree ?? new Date().getFullYear() + 1}-01-01`,
        date_fin: exercice?.date_fin ? String(exercice.date_fin).substring(0, 10) : `${annee_suggeree ?? new Date().getFullYear() + 1}-12-31`,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (mode === 'create') post('/budget/exercices');
        else put(`/budget/exercices/${exercice.id}`);
    };

    return (
        <AppLayout
            pageTitle={mode === 'create' ? 'Nouvel exercice budgétaire' : `Modifier ${exercice?.annee}`}
            breadcrumbs={[{ label: 'Budget', href: '/budget' }, { label: 'Exercices', href: '/budget/exercices' }, { label: mode === 'create' ? 'Création' : 'Édition' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Exercice budgétaire"
                    description="Paramétrage du cadre annuel de programmation budgétaire."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild><Link href="/budget/exercices"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
            </div>

            <Card className="mx-auto max-w-2xl">
                <CardHeader>
                    <CardTitle>{mode === 'create' ? 'Création d\'un exercice budgétaire' : 'Modifier l\'exercice'}</CardTitle>
                    <CardDescription>
                        L'exercice constitue le cadre annuel de toutes les lignes budgétaires (recettes et dépenses).
                    </CardDescription>
                </CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Année *</Label>
                                <Input type="number" min={2024} max={2050} value={data.annee} onChange={(e) => setData('annee', Number(e.target.value))} required />
                                {errors.annee && <p className="text-xs text-destructive">{errors.annee}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label>Devise</Label>
                                <Input value={data.devise} onChange={(e) => setData('devise', e.target.value)} placeholder="XAF" />
                            </div>
                            <div className="space-y-2 sm:col-span-1">
                                <Label>Date début</Label>
                                <Input type="date" value={data.date_debut} onChange={(e) => setData('date_debut', e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label>Libellé *</Label>
                            <Input value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                            {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Date fin</Label>
                            <Input type="date" value={data.date_fin} onChange={(e) => setData('date_fin', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Description</Label>
                            <Textarea rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/budget/exercices">Annuler</Link></Button>
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
