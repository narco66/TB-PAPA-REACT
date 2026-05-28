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

export default function PlanForm({ mode, plan, annee_suggeree }: any) {
    const annee = plan?.annee ?? annee_suggeree ?? new Date().getFullYear();
    const { data, setData, post, processing, errors } = useForm({
        annee,
        libelle: plan?.libelle ?? `Plan d'audit ${annee}`,
        description: plan?.description ?? '',
        orientation_strategique: plan?.orientation_strategique ?? '',
        date_debut: plan?.date_debut ? String(plan.date_debut).substring(0, 10) : `${annee}-01-01`,
        date_fin: plan?.date_fin ? String(plan.date_fin).substring(0, 10) : `${annee}-12-31`,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/audit/plans');
    };

    return (
        <AppLayout
            pageTitle={mode === 'create' ? "Nouveau plan d'audit" : `Plan ${plan?.annee}`}
            breadcrumbs={[{ label: 'Audit interne', href: '/audit' }, { label: 'Plans', href: '/audit/plans' }, { label: 'Création' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero title="Plan d'audit annuel" description="Cadre annuel des missions d'audit interne IGS." />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild><Link href="/audit/plans"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                </div>

                <Card className="mx-auto max-w-3xl">
                    <CardHeader>
                        <CardTitle>{mode === 'create' ? "Création d'un plan d'audit" : 'Modifier le plan'}</CardTitle>
                        <CardDescription>Le plan annuel est validé par la Direction générale (IIA Standard 2010).</CardDescription>
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
                                    <Label>Date début</Label>
                                    <Input type="date" value={data.date_debut} onChange={(e) => setData('date_debut', e.target.value)} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Date fin</Label>
                                    <Input type="date" value={data.date_fin} onChange={(e) => setData('date_fin', e.target.value)} />
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
                            <div className="space-y-2">
                                <Label>Orientation stratégique</Label>
                                <Textarea rows={4} value={data.orientation_strategique} onChange={(e) => setData('orientation_strategique', e.target.value)} placeholder="Axes prioritaires de l'audit annuel : risques majeurs, processus critiques, recommandations antérieures..." />
                            </div>
                        </CardContent>
                        <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                            <Button variant="outline" type="button" asChild><Link href="/audit/plans">Annuler</Link></Button>
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
