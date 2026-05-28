import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileDown, LoaderCircle } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Filtre {
    key: string;
    label: string;
    type: 'select' | 'date' | 'text' | 'number';
    required?: boolean;
    options?: Array<{ value: string | number; label: string }>;
    default?: any;
}

interface Props {
    rapport: {
        key: string;
        titre: string;
        description: string;
        categorie: string;
        orientation: string;
        format: string;
    };
    filtres: Filtre[];
}

export default function ReportGenerer({ rapport, filtres }: Props) {
    const initial: Record<string, any> = {};
    filtres.forEach((f) => { initial[f.key] = f.default ?? ''; });

    const form = useForm<{ filtres: Record<string, any> }>({ filtres: initial });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(`/rapports/${rapport.key}/generer`);
    };

    const setFiltre = (key: string, value: any) => {
        form.setData('filtres', { ...form.data.filtres, [key]: value });
    };

    return (
        <AppLayout
            pageTitle={rapport.titre}
            breadcrumbs={[{ label: 'Rapports', href: '/rapports' }, { label: rapport.titre }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Générer un rapport"
                    description="Paramétrage et émission des documents institutionnels."
                />
            <div className="mb-4">
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/rapports"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            </div>

            <Card className="mx-auto max-w-2xl">
                <CardHeader>
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex-1">
                            <Badge variant="secondary" className="mb-2">{rapport.categorie}</Badge>
                            <CardTitle>{rapport.titre}</CardTitle>
                            <CardDescription className="mt-2">{rapport.description}</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <form onSubmit={submit}>
                    <CardContent className="space-y-4">
                        <div className="rounded-md border bg-muted/30 p-3 text-xs space-y-1">
                            <p><strong>Format :</strong> {rapport.format}</p>
                            <p><strong>Orientation :</strong> {rapport.orientation}</p>
                            <p className="text-muted-foreground mt-2">
                                Le document généré inclura le filigrane CEEAC, un QR Code de vérification et le hash SHA-256 d'intégrité.
                            </p>
                        </div>

                        {filtres.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Aucun filtre nécessaire — le rapport sera généré avec les paramètres par défaut.</p>
                        ) : (
                            filtres.map((f) => (
                                <div key={f.key} className="space-y-2">
                                    <Label htmlFor={f.key}>
                                        {f.label}
                                        {f.required && <span className="text-destructive ml-1">*</span>}
                                    </Label>
                                    {f.type === 'select' && (
                                        <Select value={String(form.data.filtres[f.key] ?? '')} onValueChange={(v) => setFiltre(f.key, v)}>
                                            <SelectTrigger><SelectValue placeholder={`Choisir ${f.label.toLowerCase()}`} /></SelectTrigger>
                                            <SelectContent>
                                                {f.options?.map((o) => (
                                                    <SelectItem key={String(o.value)} value={String(o.value)}>{o.label}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                    {f.type === 'date' && (
                                        <Input id={f.key} type="date" value={form.data.filtres[f.key] ?? ''} onChange={(e) => setFiltre(f.key, e.target.value)} />
                                    )}
                                    {f.type === 'number' && (
                                        <Input id={f.key} type="number" value={form.data.filtres[f.key] ?? ''} onChange={(e) => setFiltre(f.key, e.target.value)} />
                                    )}
                                    {f.type === 'text' && (
                                        <Input id={f.key} value={form.data.filtres[f.key] ?? ''} onChange={(e) => setFiltre(f.key, e.target.value)} />
                                    )}
                                </div>
                            ))
                        )}
                    </CardContent>
                    <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                        <Button variant="outline" type="button" asChild><Link href="/rapports">Annuler</Link></Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <FileDown className="h-4 w-4" />}
                            Générer le PDF
                        </Button>
                    </div>
                </form>
            </Card>
                </div>
</AppLayout>
    );
}
