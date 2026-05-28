import { router, useForm } from '@inertiajs/react';
import { Building2, LoaderCircle, Plus } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { ExcelExportButton } from '@/components/common/excel-export-button';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function SuppliersIndex({ suppliers, types, statuts, filters, can }: any) {
    const items = suppliers.data ?? [];
    const [showForm, setShowForm] = useState(false);

    const setFilter = (key: string, value: string) => {
        router.get('/expense/suppliers', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const f = useForm({
        code: '', libelle: '', type: 'personne_morale', nif: '', rccm: '',
        contact_principal: '', email: '', telephone: '', adresse: '', pays: '',
        compte_bancaire: '', banque: '', observations: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.post('/expense/suppliers', { onSuccess: () => { f.reset(); setShowForm(false); } });
    };

    return (
        <AppLayout
            pageTitle="Fournisseurs"
            breadcrumbs={[{ label: 'Chaîne de la dépense', href: '/expense/requests' }, { label: 'Fournisseurs' }]}
            actions={
                <>
                    <ExcelExportButton journal="suppliers" filtres={filters} />
                    <PdfExportButton reportKey="liste_suppliers" filtres={filters} />
                    {can.manage && (
                        <Button onClick={() => setShowForm(!showForm)}>
                            <Plus className="h-4 w-4" />{showForm ? 'Annuler' : 'Nouveau fournisseur'}
                        </Button>
                    )}
                </>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Référentiel fournisseurs"
                    title="Fournisseurs institutionnels"
                    description="Personnes physiques, personnes morales, administrations et organismes publics. NIF, RCCM, coordonnées bancaires."
                    metrics={[{ icon: Building2, label: 'Fournisseurs', value: String(suppliers.total ?? items.length) }]}
                />

                {showForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouveau fournisseur</CardTitle></CardHeader>
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
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Type *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={f.data.type} onChange={(e) => f.setData('type', e.target.value)}>
                                            {types.map((t: string) => <option key={t} value={t}>{t}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>NIF</Label>
                                        <Input value={f.data.nif} onChange={(e) => f.setData('nif', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>RCCM</Label>
                                        <Input value={f.data.rccm} onChange={(e) => f.setData('rccm', e.target.value)} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Contact</Label>
                                        <Input value={f.data.contact_principal} onChange={(e) => f.setData('contact_principal', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Email</Label>
                                        <Input type="email" value={f.data.email} onChange={(e) => f.setData('email', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Téléphone</Label>
                                        <Input value={f.data.telephone} onChange={(e) => f.setData('telephone', e.target.value)} />
                                    </div>
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
                            <input
                                type="search"
                                placeholder="Code, libellé, NIF…"
                                className="rounded-md border px-3 py-2 text-sm"
                                defaultValue={filters?.q ?? ''}
                                onBlur={(e) => setFilter('q', e.target.value)}
                            />
                            <select
                                className="rounded-md border px-3 py-2 text-sm"
                                value={filters?.statut ?? ''}
                                onChange={(e) => setFilter('statut', e.target.value)}
                            >
                                <option value="">Tous statuts</option>
                                {statuts.map((s: string) => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Libellé</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>NIF</TableHead>
                                    <TableHead>Contact</TableHead>
                                    <TableHead>Statut</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={6} className="py-8 text-center text-muted-foreground">Aucun fournisseur.</TableCell></TableRow>
                                ) : items.map((s: any) => (
                                    <TableRow key={s.id}>
                                        <TableCell className="font-mono text-xs">{s.code}</TableCell>
                                        <TableCell className="font-medium">{s.libelle}</TableCell>
                                        <TableCell className="text-xs">{s.type}</TableCell>
                                        <TableCell className="text-xs">{s.nif ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{s.email ?? s.telephone ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{s.statut}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
