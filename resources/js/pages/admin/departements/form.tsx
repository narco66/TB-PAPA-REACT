import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, LoaderCircle } from 'lucide-react';
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
    departement?: any;
    commissaires: Array<{ id: number; name: string; email: string; fonction: string | null }>;
}

export default function DepartementForm({ mode, departement, commissaires }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        code: departement?.code ?? '',
        libelle: departement?.libelle ?? '',
        description: departement?.description ?? '',
        commissaire_id: departement?.commissaire_id ?? '',
        ordre: departement?.ordre ?? 0,
        actif: departement?.actif ?? true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (mode === 'create') post('/admin/departements');
        else put(`/admin/departements/${departement.id}`);
    };

    return (
        <AppLayout
            pageTitle={mode === 'create' ? 'Nouveau Département' : `Modifier ${departement?.code}`}
            breadcrumbs={[
                { label: 'Administration' },
                { label: 'Départements', href: '/admin/departements' },
                { label: mode === 'create' ? 'Création' : 'Édition' },
            ]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Département technique"
                    description="Création et mise à jour du référentiel organisationnel."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/admin/departements"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-3xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Building2 className="h-5 w-5" />
                        {mode === 'create' ? 'Création d\'un département technique' : 'Modifier le département'}
                    </CardTitle>
                    <CardDescription>
                        Un département technique est placé sous l'autorité d'un Commissaire et regroupe une ou plusieurs Directions techniques.
                        Il porte les axes stratégiques du PAPA dans son périmètre sectoriel (Section 1.3 du CDC).
                    </CardDescription>
                </CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="code">Code *</Label>
                                <Input
                                    id="code"
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                    required
                                    placeholder="DAEC, DIEM…"
                                    maxLength={16}
                                    className="font-mono"
                                />
                                {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="libelle">Libellé complet *</Label>
                                <Input
                                    id="libelle"
                                    value={data.libelle}
                                    onChange={(e) => setData('libelle', e.target.value)}
                                    required
                                    placeholder="Direction de l'Aménagement, Économie et Commerce"
                                />
                                {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description / Mandat sectoriel</Label>
                            <Textarea
                                id="description"
                                rows={3}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                placeholder="Périmètre, missions, politiques sectorielles couvertes…"
                            />
                            {errors.description && <p className="text-xs text-destructive">{errors.description}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label>Commissaire (Chef de Département)</Label>
                            <Select
                                value={String(data.commissaire_id ?? '')}
                                onValueChange={(v) => setData('commissaire_id', v === 'none' ? '' : Number(v))}
                            >
                                <SelectTrigger><SelectValue placeholder="Non désigné" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Non désigné</SelectItem>
                                    {commissaires.map((c) => (
                                        <SelectItem key={c.id} value={String(c.id)}>
                                            {c.name} — {c.fonction ?? c.email}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-muted-foreground">
                                Seuls les utilisateurs ayant le rôle « Commissaire » sont éligibles.
                            </p>
                            {errors.commissaire_id && <p className="text-xs text-destructive">{errors.commissaire_id}</p>}
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="ordre">Ordre d'affichage</Label>
                                <Input
                                    id="ordre"
                                    type="number"
                                    min={0}
                                    max={9999}
                                    value={data.ordre}
                                    onChange={(e) => setData('ordre', Number(e.target.value))}
                                />
                                <p className="text-xs text-muted-foreground">Plus le chiffre est petit, plus le département apparaît en haut.</p>
                            </div>
                            <div className="space-y-2 flex flex-col">
                                <Label>État</Label>
                                <label className="flex items-center gap-2 text-sm cursor-pointer rounded-md border px-3 py-2 hover:bg-accent">
                                    <input
                                        type="checkbox"
                                        checked={data.actif}
                                        onChange={(e) => setData('actif', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                    Département actif
                                </label>
                            </div>
                        </div>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild>
                            <Link href="/admin/departements">Annuler</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            {mode === 'create' ? 'Créer le département' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
