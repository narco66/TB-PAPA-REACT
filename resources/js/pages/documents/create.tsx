import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileText, LoaderCircle, Upload } from 'lucide-react';
import { type FormEvent, useMemo } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    attachables: {
        papa: Array<{ id: number; libelle: string; annee: number }>;
        action: Array<{ id: number; code: string; libelle: string; papa?: { annee: number } }>;
        activite: Array<{ id: number; code: string; libelle: string }>;
        indicateur: Array<{ id: number; code: string; libelle: string }>;
    };
}

export default function DocumentCreate({ attachables }: Props) {
    const { data, setData, post, processing, errors, progress } = useForm<{
        documentable_type: string;
        documentable_id: number | '';
        categorie: string;
        libelle: string;
        description: string;
        confidentiel: boolean;
        fichier: File | null;
    }>({
        documentable_type: 'action',
        documentable_id: '' as number | '',
        categorie: 'execution',
        libelle: '',
        description: '',
        confidentiel: false,
        fichier: null,
    });

    const options = useMemo(() => {
        switch (data.documentable_type) {
            case 'papa':
                return attachables.papa.map((p) => ({ id: p.id, label: `PAPA ${p.annee} — ${p.libelle}` }));
            case 'action':
                return attachables.action.map((a) => ({ id: a.id, label: `${a.code} — ${a.libelle}` }));
            case 'activite':
                return attachables.activite.map((a) => ({ id: a.id, label: `${a.code} — ${a.libelle}` }));
            case 'indicateur':
                return attachables.indicateur.map((i) => ({ id: i.id, label: `${i.code} — ${i.libelle}` }));
            default:
                return [];
        }
    }, [data.documentable_type, attachables]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/documents', { forceFormData: true });
    };

    return (
        <AppLayout
            pageTitle="Déposer un document"
            breadcrumbs={[{ label: 'GED', href: '/documents' }, { label: 'Dépôt' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Déposer un document"
                    description="Ajout d'une preuve documentaire rattachée au PAPA et aux entités de suivi."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/documents"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-2xl">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Upload className="h-5 w-5" />
                        Dépôt dans la Gestion Électronique des Documents
                    </CardTitle>
                    <CardDescription>
                        Tout document devient une preuve auditable horodatée et associée à une entité du PAPA.
                    </CardDescription>
                </CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Type d'entité *</Label>
                                <Select value={data.documentable_type} onValueChange={(v) => { setData('documentable_type', v); setData('documentable_id', ''); }}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="papa">PAPA</SelectItem>
                                        <SelectItem value="action">Action prioritaire</SelectItem>
                                        <SelectItem value="activite">Activité</SelectItem>
                                        <SelectItem value="indicateur">Indicateur</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Entité rattachée *</Label>
                                <Select value={String(data.documentable_id)} onValueChange={(v) => setData('documentable_id', Number(v))}>
                                    <SelectTrigger><SelectValue placeholder="Choisir…" /></SelectTrigger>
                                    <SelectContent>
                                        {options.map((o) => (<SelectItem key={o.id} value={String(o.id)}>{o.label}</SelectItem>))}
                                    </SelectContent>
                                </Select>
                                {errors.documentable_id && <p className="text-xs text-destructive">{errors.documentable_id}</p>}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Catégorie *</Label>
                            <Select value={data.categorie} onValueChange={(v) => setData('categorie', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="execution">Exécution (rapports, PV, livrables)</SelectItem>
                                    <SelectItem value="validation">Validation (notes, visas, décisions)</SelectItem>
                                    <SelectItem value="financier">Financier (états budgétaires, pièces)</SelectItem>
                                    <SelectItem value="suivi_evaluation">Suivi-évaluation (fiches indicateurs, rapports)</SelectItem>
                                    <SelectItem value="autre">Autre</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="libelle">Libellé du document *</Label>
                            <Input id="libelle" value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                            {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description / contexte</Label>
                            <Textarea id="description" rows={2} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="fichier">Fichier (max 25 Mo) *</Label>
                            <div className="rounded-md border-2 border-dashed bg-muted/20 p-6 text-center">
                                <FileText className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                                <Input
                                    id="fichier"
                                    type="file"
                                    onChange={(e) => setData('fichier', e.target.files?.[0] ?? null)}
                                    required
                                    className="cursor-pointer"
                                />
                                {data.fichier && (
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {data.fichier.name} ({(data.fichier.size / 1024).toFixed(1)} ko)
                                    </p>
                                )}
                            </div>
                            {errors.fichier && <p className="text-xs text-destructive">{errors.fichier}</p>}
                            {progress ? <p className="text-xs text-muted-foreground">Upload : {progress.percentage}%</p> : null}
                        </div>

                        <label className="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" checked={data.confidentiel} onChange={(e) => setData('confidentiel', e.target.checked)} className="h-4 w-4" />
                            Marquer ce document comme confidentiel (accès restreint)
                        </label>
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/documents">Annuler</Link></Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            <Upload className="h-4 w-4" />
                            Déposer dans la GED
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
