import { router, useForm } from '@inertiajs/react';
import { Building2, LoaderCircle, Plus } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

export default function DirectionsIndex({ directions, departements, types, filters, can }: any) {
    const items = directions.data ?? [];
    const [showForm, setShowForm] = useState(false);

    const setFilter = (key: string, value: string) => {
        router.get('/admin/directions', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const f = useForm<any>({
        code: '', libelle: '', description: '', type: 'technique',
        departement_id: '', directeur_id: '', ordre: 0,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.post('/admin/directions', { onSuccess: () => { f.reset(); setShowForm(false); } });
    };

    return (
        <AppLayout
            pageTitle="Directions"
            breadcrumbs={[{ label: 'Administration' }, { label: 'Directions' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={items} filename="directions" meta={{ total: directions.total ?? items.length, filtres: filters }} />
                    {can.manage && (
                        <Button onClick={() => setShowForm(!showForm)}>
                            <Plus className="h-4 w-4" />{showForm ? 'Annuler' : 'Nouvelle direction'}
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Hiérarchie organisationnelle"
                    title="Directions"
                    description="Niveau 2 de la hiérarchie : Département → Direction → Service. Direction technique (sectorielle) ou Direction d'appui et de soutien."
                    metrics={[{ icon: Building2, label: 'Directions', value: String(directions.total ?? items.length) }]}
                />

                {showForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouvelle direction</CardTitle></CardHeader>
                        <form onSubmit={submit}>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Code *</Label>
                                        <Input value={f.data.code} onChange={(e) => f.setData('code', e.target.value)} required />
                                        {f.errors.code && <p className="text-xs text-destructive">{f.errors.code}</p>}
                                    </div>
                                    <div className="space-y-2 sm:col-span-2">
                                        <Label>Libellé *</Label>
                                        <Input value={f.data.libelle} onChange={(e) => f.setData('libelle', e.target.value)} required />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Type *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={f.data.type} onChange={(e) => f.setData('type', e.target.value)}>
                                            {types.map((t: string) => <option key={t} value={t}>{t}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Département</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={f.data.departement_id} onChange={(e) => f.setData('departement_id', Number(e.target.value) || '')}>
                                            <option value="">—</option>
                                            {departements.map((d: any) => <option key={d.id} value={d.id}>{d.libelle}</option>)}
                                        </select>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Description</Label>
                                    <Textarea rows={2} value={f.data.description} onChange={(e) => f.setData('description', e.target.value)} />
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button type="button" variant="outline" onClick={() => setShowForm(false)}>Annuler</Button>
                                <Button type="submit" disabled={f.processing}>
                                    {f.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Créer
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                <Card>
                    <CardContent className="p-4 space-y-4">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <input type="search" placeholder="Code ou libellé…" className="rounded-md border px-3 py-2 text-sm" defaultValue={filters?.q ?? ''} onBlur={(e) => setFilter('q', e.target.value)} />
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters?.departement_id ?? ''} onChange={(e) => setFilter('departement_id', e.target.value)}>
                                <option value="">Tous départements</option>
                                {departements.map((d: any) => <option key={d.id} value={d.id}>{d.libelle}</option>)}
                            </select>
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters?.type ?? ''} onChange={(e) => setFilter('type', e.target.value)}>
                                <option value="">Tous types</option>
                                {types.map((t: string) => <option key={t} value={t}>{t}</option>)}
                            </select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Département</TableHead>
                                    <TableHead>Directeur</TableHead>
                                    <TableHead className="text-right">Services</TableHead>
                                    <TableHead className="text-right">Agents</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground">Aucune direction.</TableCell></TableRow>
                                ) : items.map((d: any) => (
                                    <TableRow key={d.id}>
                                        <TableCell className="font-mono text-xs">{d.code}</TableCell>
                                        <TableCell className="font-medium">{d.libelle}</TableCell>
                                        <TableCell className="text-xs">{d.type}</TableCell>
                                        <TableCell className="text-xs">{d.departement?.code ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{d.directeur?.name ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums">{d.services_count ?? 0}</TableCell>
                                        <TableCell className="text-right tabular-nums">{d.utilisateurs_count ?? 0}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <Pagination pagination={directions} label="directions" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
