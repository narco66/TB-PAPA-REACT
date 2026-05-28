import { router, useForm } from '@inertiajs/react';
import { Building2, LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { ExcelExportButton } from '@/components/common/excel-export-button';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

interface SupplierData {
    id: number;
    code: string;
    libelle: string;
    type: string;
    nif?: string | null;
    rccm?: string | null;
    contact_principal?: string | null;
    email?: string | null;
    telephone?: string | null;
    adresse?: string | null;
    pays?: string | null;
    compte_bancaire?: string | null;
    banque?: string | null;
    statut: string;
    observations?: string | null;
}

export default function SuppliersIndex({ suppliers, types, statuts, filters, can }: any) {
    const items: SupplierData[] = suppliers.data ?? [];
    const [showForm, setShowForm] = useState(false);
    const [editing, setEditing] = useState<SupplierData | null>(null);

    const setFilter = (key: string, value: string) => {
        router.get('/expense/suppliers', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    // Form création
    const fCreate = useForm({
        code: '', libelle: '', type: 'personne_morale', nif: '', rccm: '',
        contact_principal: '', email: '', telephone: '', adresse: '', pays: '',
        compte_bancaire: '', banque: '', observations: '',
    });

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        fCreate.post('/expense/suppliers', { onSuccess: () => { fCreate.reset(); setShowForm(false); } });
    };

    // Form édition
    const fEdit = useForm({
        code: '', libelle: '', type: 'personne_morale', statut: 'actif',
        nif: '', rccm: '', contact_principal: '', email: '', telephone: '',
        adresse: '', pays: '', compte_bancaire: '', banque: '', observations: '',
    });

    const openEdit = (s: SupplierData) => {
        setEditing(s);
        fEdit.setData({
            code: s.code,
            libelle: s.libelle,
            type: s.type,
            statut: s.statut,
            nif: s.nif ?? '',
            rccm: s.rccm ?? '',
            contact_principal: s.contact_principal ?? '',
            email: s.email ?? '',
            telephone: s.telephone ?? '',
            adresse: s.adresse ?? '',
            pays: s.pays ?? '',
            compte_bancaire: s.compte_bancaire ?? '',
            banque: s.banque ?? '',
            observations: s.observations ?? '',
        });
        setShowForm(false);
    };

    const submitEdit = (e: FormEvent) => {
        e.preventDefault();
        if (!editing) return;
        fEdit.put(`/expense/suppliers/${editing.id}`, {
            preserveScroll: true,
            onSuccess: () => { fEdit.reset(); setEditing(null); },
        });
    };

    const destroy = (s: SupplierData) => {
        if (confirm(`Archiver le fournisseur « ${s.libelle} » ? Cette action est réversible.`)) {
            router.delete(`/expense/suppliers/${s.id}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout
            pageTitle="Fournisseurs"
            breadcrumbs={[{ label: 'Chaîne de la dépense', href: '/expense/requests' }, { label: 'Fournisseurs' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={items} filename="fournisseurs" meta={{ total: suppliers.total ?? items.length, filtres: filters }} />
                    <ExcelExportButton journal="suppliers" filtres={filters} />
                    <PdfExportButton reportKey="liste_suppliers" filtres={filters} />
                    {can.manage && (
                        <Button onClick={() => { setShowForm(!showForm); setEditing(null); }}>
                            <Plus className="h-4 w-4" />{showForm ? 'Annuler' : 'Nouveau fournisseur'}
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Référentiel fournisseurs"
                    title="Fournisseurs institutionnels"
                    description="Personnes physiques, personnes morales, administrations et organismes publics. NIF, RCCM, coordonnées bancaires."
                    metrics={[{ icon: Building2, label: 'Fournisseurs', value: String(suppliers.total ?? items.length) }]}
                />

                {/* === FORMULAIRE CRÉATION === */}
                {showForm && (
                    <Card>
                        <CardHeader><CardTitle>Nouveau fournisseur</CardTitle></CardHeader>
                        <form onSubmit={submitCreate}>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Code *</Label>
                                        <Input value={fCreate.data.code} onChange={(e) => fCreate.setData('code', e.target.value)} required />
                                        {fCreate.errors.code && <p className="text-xs text-destructive">{fCreate.errors.code}</p>}
                                    </div>
                                    <div className="space-y-2 sm:col-span-2">
                                        <Label>Libellé *</Label>
                                        <Input value={fCreate.data.libelle} onChange={(e) => fCreate.setData('libelle', e.target.value)} required />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Type *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={fCreate.data.type} onChange={(e) => fCreate.setData('type', e.target.value)}>
                                            {types.map((t: string) => <option key={t} value={t}>{t}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>NIF</Label>
                                        <Input value={fCreate.data.nif} onChange={(e) => fCreate.setData('nif', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>RCCM</Label>
                                        <Input value={fCreate.data.rccm} onChange={(e) => fCreate.setData('rccm', e.target.value)} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Contact</Label>
                                        <Input value={fCreate.data.contact_principal} onChange={(e) => fCreate.setData('contact_principal', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Email</Label>
                                        <Input type="email" value={fCreate.data.email} onChange={(e) => fCreate.setData('email', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Téléphone</Label>
                                        <Input value={fCreate.data.telephone} onChange={(e) => fCreate.setData('telephone', e.target.value)} />
                                    </div>
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button type="button" variant="outline" onClick={() => setShowForm(false)}>Annuler</Button>
                                <Button type="submit" disabled={fCreate.processing}>
                                    {fCreate.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Créer
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                {/* === FORMULAIRE ÉDITION === */}
                {editing && (
                    <Card className="border-ceeac-blue/40 shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Pencil className="h-4 w-4 text-ceeac-blue" />
                                Modifier le fournisseur — <span className="font-mono text-sm">{editing.code}</span>
                            </CardTitle>
                        </CardHeader>
                        <form onSubmit={submitEdit}>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Code *</Label>
                                        <Input value={fEdit.data.code} onChange={(e) => fEdit.setData('code', e.target.value)} required />
                                        {fEdit.errors.code && <p className="text-xs text-destructive">{fEdit.errors.code}</p>}
                                    </div>
                                    <div className="space-y-2 sm:col-span-2">
                                        <Label>Libellé *</Label>
                                        <Input value={fEdit.data.libelle} onChange={(e) => fEdit.setData('libelle', e.target.value)} required />
                                        {fEdit.errors.libelle && <p className="text-xs text-destructive">{fEdit.errors.libelle}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Type *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={fEdit.data.type} onChange={(e) => fEdit.setData('type', e.target.value)}>
                                            {types.map((t: string) => <option key={t} value={t}>{t}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Statut *</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={fEdit.data.statut} onChange={(e) => fEdit.setData('statut', e.target.value)}>
                                            {statuts.map((s: string) => <option key={s} value={s}>{s}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Pays</Label>
                                        <Input value={fEdit.data.pays} onChange={(e) => fEdit.setData('pays', e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>NIF</Label>
                                        <Input value={fEdit.data.nif} onChange={(e) => fEdit.setData('nif', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>RCCM</Label>
                                        <Input value={fEdit.data.rccm} onChange={(e) => fEdit.setData('rccm', e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Contact principal</Label>
                                        <Input value={fEdit.data.contact_principal} onChange={(e) => fEdit.setData('contact_principal', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Email</Label>
                                        <Input type="email" value={fEdit.data.email} onChange={(e) => fEdit.setData('email', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Téléphone</Label>
                                        <Input value={fEdit.data.telephone} onChange={(e) => fEdit.setData('telephone', e.target.value)} />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Compte bancaire</Label>
                                        <Input value={fEdit.data.compte_bancaire} onChange={(e) => fEdit.setData('compte_bancaire', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Banque</Label>
                                        <Input value={fEdit.data.banque} onChange={(e) => fEdit.setData('banque', e.target.value)} />
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label>Adresse</Label>
                                    <Textarea rows={2} value={fEdit.data.adresse} onChange={(e) => fEdit.setData('adresse', e.target.value)} />
                                </div>

                                <div className="space-y-2">
                                    <Label>Observations</Label>
                                    <Textarea rows={2} value={fEdit.data.observations} onChange={(e) => fEdit.setData('observations', e.target.value)} />
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button type="button" variant="outline" onClick={() => setEditing(null)}>Annuler</Button>
                                <Button type="submit" disabled={fEdit.processing}>
                                    {fEdit.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Enregistrer les modifications
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                {/* === LISTE === */}
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
                                    {can.manage && <TableHead className="text-right">Actions</TableHead>}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={can.manage ? 7 : 6} className="py-8 text-center text-muted-foreground">Aucun fournisseur.</TableCell></TableRow>
                                ) : items.map((s) => (
                                    <TableRow key={s.id} className={s.statut === 'archive' ? 'opacity-60' : ''}>
                                        <TableCell className="font-mono text-xs">{s.code}</TableCell>
                                        <TableCell className="font-medium">{s.libelle}</TableCell>
                                        <TableCell className="text-xs">{s.type}</TableCell>
                                        <TableCell className="text-xs">{s.nif ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{s.email ?? s.telephone ?? '—'}</TableCell>
                                        <TableCell className="text-xs">
                                            <span className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${
                                                s.statut === 'actif' ? 'bg-emerald-100 text-emerald-700' :
                                                s.statut === 'suspendu' ? 'bg-amber-100 text-amber-700' :
                                                'bg-muted text-muted-foreground'
                                            }`}>
                                                {s.statut}
                                            </span>
                                        </TableCell>
                                        {can.manage && (
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => openEdit(s)}
                                                        title="Modifier"
                                                    >
                                                        <Pencil className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => destroy(s)}
                                                        title="Archiver"
                                                        className="hover:text-destructive"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <Pagination pagination={suppliers} label="fournisseurs" />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
