import { router, useForm } from '@inertiajs/react';
import { CheckCircle2, ClipboardCheck, FileDown, LoaderCircle, Plus } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

export default function ServiceFaitIndex({ certificats, receptions, conformites, statuts, types_reception, natures, filters }: any) {
    const [showCertForm, setShowCertForm] = useState(false);
    const [showPvForm, setShowPvForm] = useState(false);

    const certs = certificats.data ?? [];
    const pvs = receptions ?? [];

    const fmt = (n: number) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(n || 0));

    const certForm = useForm<any>({
        budget_mouvement_id: '',
        date_constatation: new Date().toISOString().substring(0, 10),
        description: '',
        montant_constate: 0,
        conformite_qualitative: 'conforme',
        conformite_quantitative: 'conforme',
        observations: '',
    });

    const pvForm = useForm<any>({
        budget_mouvement_id: '',
        service_done_certificate_id: '',
        date_reception: new Date().toISOString().substring(0, 10),
        type_reception: 'definitive',
        nature: 'biens',
        quantite_recue: 0,
        unite_mesure: '',
        montant_recu: 0,
        conformite: 'conforme',
        reserves: '',
        observations: '',
        president_commission_id: '',
        membre1_commission_id: '',
        membre2_commission_id: '',
    });

    const submitCert = (e: FormEvent) => {
        e.preventDefault();
        certForm.post('/expense/service-fait/certificats', {
            onSuccess: () => { certForm.reset(); setShowCertForm(false); },
            preserveScroll: true,
        });
    };

    const submitPv = (e: FormEvent) => {
        e.preventDefault();
        pvForm.post('/expense/service-fait/receptions', {
            onSuccess: () => { pvForm.reset(); setShowPvForm(false); },
            preserveScroll: true,
        });
    };

    const validateCert = (id: number) => router.post(`/expense/service-fait/certificats/${id}/validate`, {}, { preserveScroll: true });
    const validatePv = (id: number) => router.post(`/expense/service-fait/receptions/${id}/validate`, {}, { preserveScroll: true });

    return (
        <AppLayout
            pageTitle="Service fait & Réception"
            breadcrumbs={[{ label: 'Chaîne de la dépense', href: '/expense/requests' }, { label: 'Service fait' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={{ certificats: certs, receptions: pvs }} filename="service_fait" meta={{ certificats: certs.length, receptions: pvs.length, filtres: filters }} />
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Chaîne de la dépense — Étape 6"
                    title="Service fait & Réception"
                    description="Constatation institutionnelle de l'exécution. Certificats de service fait (prestations) + Procès-verbaux de réception (biens, services, travaux)."
                    metrics={[
                        { icon: ClipboardCheck, label: 'Certificats SF', value: String(certificats.total ?? certs.length) },
                        { icon: CheckCircle2, label: 'PV Réception', value: String(pvs.length) },
                    ]}
                />

                {/* === Certificats de service fait === */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Certificats de service fait</CardTitle>
                        <Button onClick={() => setShowCertForm(!showCertForm)} size="sm">
                            <Plus className="h-4 w-4" />{showCertForm ? 'Annuler' : 'Nouveau certificat'}
                        </Button>
                    </CardHeader>

                    {showCertForm && (
                        <form onSubmit={submitCert}>
                            <CardContent className="space-y-4 border-b">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Mouvement engagement (ID) *</Label>
                                        <Input type="number" required value={certForm.data.budget_mouvement_id} onChange={(e) => certForm.setData('budget_mouvement_id', Number(e.target.value))} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Date constatation *</Label>
                                        <Input type="date" required value={certForm.data.date_constatation} onChange={(e) => certForm.setData('date_constatation', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Montant constaté *</Label>
                                        <Input type="number" step="0.01" required value={certForm.data.montant_constate} onChange={(e) => certForm.setData('montant_constate', Number(e.target.value))} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Description *</Label>
                                    <Textarea rows={3} required value={certForm.data.description} onChange={(e) => certForm.setData('description', e.target.value)} />
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Conformité qualitative</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={certForm.data.conformite_qualitative} onChange={(e) => certForm.setData('conformite_qualitative', e.target.value)}>
                                            {conformites.map((c: string) => <option key={c} value={c}>{c}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Conformité quantitative</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={certForm.data.conformite_quantitative} onChange={(e) => certForm.setData('conformite_quantitative', e.target.value)}>
                                            {conformites.map((c: string) => <option key={c} value={c}>{c}</option>)}
                                        </select>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Observations</Label>
                                    <Textarea rows={2} value={certForm.data.observations} onChange={(e) => certForm.setData('observations', e.target.value)} />
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-3">
                                <Button type="submit" disabled={certForm.processing}>
                                    {certForm.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Créer le certificat
                                </Button>
                            </div>
                        </form>
                    )}

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Référence</TableHead>
                                    <TableHead>Engagement</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead className="text-right">Montant constaté</TableHead>
                                    <TableHead>Conformité</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {certs.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground">Aucun certificat.</TableCell></TableRow>
                                ) : certs.map((c: any) => (
                                    <TableRow key={c.id}>
                                        <TableCell className="font-mono text-xs">{c.reference}</TableCell>
                                        <TableCell className="text-xs">{c.mouvement?.reference ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{new Date(c.date_constatation).toLocaleDateString('fr-FR')}</TableCell>
                                        <TableCell className="text-right tabular-nums">{fmt(c.montant_constate)}</TableCell>
                                        <TableCell className="text-xs">{c.conformite_qualitative} / {c.conformite_quantitative}</TableCell>
                                        <TableCell className="text-xs">{c.statut}</TableCell>
                                        <TableCell className="flex gap-1">
                                            {c.statut === 'projet' && (
                                                <Button size="sm" variant="outline" onClick={() => validateCert(c.id)}>
                                                    <CheckCircle2 className="h-3 w-3" />Valider
                                                </Button>
                                            )}
                                            <Button size="sm" variant="ghost" asChild>
                                                <a href={`/rapports/certificat_service_fait/quick?certificat_id=${c.id}`} target="_blank" rel="noopener noreferrer">
                                                    <FileDown className="h-3 w-3" />PDF
                                                </a>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* === Procès-verbaux de réception === */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Procès-verbaux de réception</CardTitle>
                        <Button onClick={() => setShowPvForm(!showPvForm)} size="sm">
                            <Plus className="h-4 w-4" />{showPvForm ? 'Annuler' : 'Nouveau PV'}
                        </Button>
                    </CardHeader>

                    {showPvForm && (
                        <form onSubmit={submitPv}>
                            <CardContent className="space-y-4 border-b">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Mouvement engagement (ID) *</Label>
                                        <Input type="number" required value={pvForm.data.budget_mouvement_id} onChange={(e) => pvForm.setData('budget_mouvement_id', Number(e.target.value))} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Date réception *</Label>
                                        <Input type="date" required value={pvForm.data.date_reception} onChange={(e) => pvForm.setData('date_reception', e.target.value)} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Montant reçu *</Label>
                                        <Input type="number" step="0.01" required value={pvForm.data.montant_recu} onChange={(e) => pvForm.setData('montant_recu', Number(e.target.value))} />
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Type de réception</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={pvForm.data.type_reception} onChange={(e) => pvForm.setData('type_reception', e.target.value)}>
                                            {types_reception.map((t: string) => <option key={t} value={t}>{t}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Nature</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={pvForm.data.nature} onChange={(e) => pvForm.setData('nature', e.target.value)}>
                                            {natures.map((n: string) => <option key={n} value={n}>{n}</option>)}
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Conformité</Label>
                                        <select className="w-full rounded-md border px-3 py-2 text-sm" value={pvForm.data.conformite} onChange={(e) => pvForm.setData('conformite', e.target.value)}>
                                            {['conforme', 'reserves', 'non_conforme'].map((c) => <option key={c} value={c}>{c}</option>)}
                                        </select>
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Quantité reçue</Label>
                                        <Input type="number" step="0.001" value={pvForm.data.quantite_recue} onChange={(e) => pvForm.setData('quantite_recue', Number(e.target.value))} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Unité de mesure</Label>
                                        <Input value={pvForm.data.unite_mesure} onChange={(e) => pvForm.setData('unite_mesure', e.target.value)} placeholder="ex: unités, kg, m²…" />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label>Réserves</Label>
                                    <Textarea rows={2} value={pvForm.data.reserves} onChange={(e) => pvForm.setData('reserves', e.target.value)} />
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label>Président commission (user ID)</Label>
                                        <Input type="number" value={pvForm.data.president_commission_id} onChange={(e) => pvForm.setData('president_commission_id', Number(e.target.value) || '')} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Membre 1</Label>
                                        <Input type="number" value={pvForm.data.membre1_commission_id} onChange={(e) => pvForm.setData('membre1_commission_id', Number(e.target.value) || '')} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Membre 2</Label>
                                        <Input type="number" value={pvForm.data.membre2_commission_id} onChange={(e) => pvForm.setData('membre2_commission_id', Number(e.target.value) || '')} />
                                    </div>
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-3">
                                <Button type="submit" disabled={pvForm.processing}>
                                    {pvForm.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Créer le PV
                                </Button>
                            </div>
                        </form>
                    )}

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Référence</TableHead>
                                    <TableHead>Engagement</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Nature</TableHead>
                                    <TableHead className="text-right">Montant</TableHead>
                                    <TableHead>Conformité</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pvs.length === 0 ? (
                                    <TableRow><TableCell colSpan={9} className="py-8 text-center text-muted-foreground">Aucun PV.</TableCell></TableRow>
                                ) : pvs.map((r: any) => (
                                    <TableRow key={r.id}>
                                        <TableCell className="font-mono text-xs">{r.reference}</TableCell>
                                        <TableCell className="text-xs">{r.mouvement?.reference ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{new Date(r.date_reception).toLocaleDateString('fr-FR')}</TableCell>
                                        <TableCell className="text-xs">{r.type_reception}</TableCell>
                                        <TableCell className="text-xs">{r.nature}</TableCell>
                                        <TableCell className="text-right tabular-nums">{fmt(r.montant_recu)}</TableCell>
                                        <TableCell className="text-xs">{r.conformite}</TableCell>
                                        <TableCell className="text-xs">{r.statut}</TableCell>
                                        <TableCell className="flex gap-1">
                                            {r.statut === 'projet' && (
                                                <Button size="sm" variant="outline" onClick={() => validatePv(r.id)}>
                                                    <CheckCircle2 className="h-3 w-3" />Valider
                                                </Button>
                                            )}
                                            <Button size="sm" variant="ghost" asChild>
                                                <a href={`/rapports/pv_reception/quick?reception_id=${r.id}`} target="_blank" rel="noopener noreferrer">
                                                    <FileDown className="h-3 w-3" />PDF
                                                </a>
                                            </Button>
                                        </TableCell>
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
