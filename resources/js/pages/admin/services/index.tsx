import { router, useForm } from '@inertiajs/react';
import { Boxes, LoaderCircle, Plus } from 'lucide-react';
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

export default function ServicesIndex({ services, departements, directions, statuts, filters, can }: any) {
    const items = services.data ?? [];
    const [showForm, setShowForm] = useState(false);

    const setFilter = (key: string, value: string) => {
        router.get('/admin/services', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const f = useForm<any>({
        code: '', libelle: '', description: '',
        direction_id: '', chef_service_id: '', ordre: 0, statut: 'actif',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.post('/admin/services', { onSuccess: () => { f.reset(); setShowForm(false); } });
    };

    const directionsFiltrees = filters?.departement_id
        ? directions.filter((d: any) => d.departement_id == filters.departement_id)
        : directions;

    return (
        <AppLayout
            pageTitle="Services"
            breadcrumbs={[{ label: 'Administration' }, { label: 'Services' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={items} filename="services" meta={{ total: services.total ?? items.length, filtres: filters }} />
                    {can.manage && (
                        <Button onClick={() => setShowForm(!showForm)}>
                            <Plus className="h-4 w-4" />{showForm ? 'Annuler' : 'Nouveau service'}
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Hiérarchie organisationnelle"
                    title="Services"
                    description="Niveau 3 de la hiérarchie institutionnelle CEEAC : Département → Direction → Service. Unité opérationnelle dirigée par un chef de service."
                    metrics={[{ icon: Boxes, label: 'Services', value: String(services.total ?? items.length) }]}
                />

                {showForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouveau service</CardTitle></CardHeader>
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
                                        <Label>Direction *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={f.data.direction_id} onChange={(e) => f.setData('direction_id', Number(e.target.value) || '')} required>
                                            <option value="">— Choisir —</option>
                                            {directions.map((d: any) => <option key={d.id} value={d.id}>{d.code} — {d.libelle}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Statut</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={f.data.statut} onChange={(e) => f.setData('statut', e.target.value)}>
                                            {statuts.map((s: string) => <option key={s} value={s}>{s}</option>)}
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
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                            <input type="search" placeholder="Code ou libellé…" className="rounded-md border px-3 py-2 text-sm" defaultValue={filters?.q ?? ''} onBlur={(e) => setFilter('q', e.target.value)} />
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters?.departement_id ?? ''} onChange={(e) => setFilter('departement_id', e.target.value)}>
                                <option value="">Tous départements</option>
                                {departements.map((d: any) => <option key={d.id} value={d.id}>{d.libelle}</option>)}
                            </select>
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters?.direction_id ?? ''} onChange={(e) => setFilter('direction_id', e.target.value)}>
                                <option value="">Toutes directions</option>
                                {directionsFiltrees.map((d: any) => <option key={d.id} value={d.id}>{d.libelle}</option>)}
                            </select>
                            <select className="rounded-md border px-3 py-2 text-sm" value={filters?.statut ?? ''} onChange={(e) => setFilter('statut', e.target.value)}>
                                <option value="">Tous statuts</option>
                                {statuts.map((s: string) => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Département</TableHead>
                                    <TableHead>Direction</TableHead>
                                    <TableHead>Chef</TableHead>
                                    <TableHead className="text-right">Agents</TableHead>
                                    <TableHead>Statut</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground">Aucun service.</TableCell></TableRow>
                                ) : items.map((s: any) => (
                                    <TableRow key={s.id}>
                                        <TableCell className="font-mono text-xs">{s.code}</TableCell>
                                        <TableCell className="font-medium">{s.libelle}</TableCell>
                                        <TableCell className="text-xs">{s.departement?.code ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{s.direction?.code ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{s.chef_service?.name ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums">{s.users_count ?? 0}</TableCell>
                                        <TableCell className="text-xs">{s.statut}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <Pagination pagination={services} label="services" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
