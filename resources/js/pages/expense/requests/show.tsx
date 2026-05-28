import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, FileDown, LoaderCircle, Send, X } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';

export default function ExpenseRequestShow({ request, types_engagement, can }: any) {
    const [showMotif, setShowMotif] = useState<null | 'reject' | 'return' | 'cancel'>(null);
    const motifForm = useForm({ motif: '', commentaire: '' });
    const engagerForm = useForm<any>({
        imputations: [{ budget_ligne_id: '', libelle: request.objet, montant: request.montant_estime, montant_ceeac: request.montant_estime_ceeac, montant_ptf: request.montant_estime_ptf, source_financement_id: request.source_financement_id }],
        numero_piece: request.numero,
    });

    const fmt = (n: number) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(n || 0));

    const submit = (action: string) => router.post(`/expense/requests/${request.id}/${action}`, {}, { preserveScroll: true });

    const submitMotif = (action: 'reject' | 'return' | 'cancel') => (e: FormEvent) => {
        e.preventDefault();
        motifForm.post(`/expense/requests/${request.id}/${action}`, {
            onSuccess: () => { motifForm.reset(); setShowMotif(null); },
            preserveScroll: true,
        });
    };

    const submitValidate = (e: FormEvent) => {
        e.preventDefault();
        motifForm.post(`/expense/requests/${request.id}/validate`, {
            onSuccess: () => motifForm.reset(),
            preserveScroll: true,
        });
    };

    const submitEngager = (e: FormEvent) => {
        e.preventDefault();
        engagerForm.post(`/expense/requests/${request.id}/engage`, { preserveScroll: true });
    };

    return (
        <AppLayout
            pageTitle={`Expression ${request.numero}`}
            breadcrumbs={[
                { label: 'Chaîne de la dépense', href: '/expense/requests' },
                { label: 'Expressions', href: '/expense/requests' },
                { label: request.numero },
            ]}
            actions={
                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline" size="sm">
                        <a href={`/rapports/fiche_expression_besoin/quick?expense_request_id=${request.id}`} target="_blank" rel="noopener noreferrer">
                            <FileDown className="h-4 w-4" />Fiche besoin
                        </a>
                    </Button>
                    {['soumis', 'en_validation_hierarchique', 'valide'].includes(request.statut) && (
                        <Button asChild variant="outline" size="sm">
                            <a href={`/rapports/demande_visa_financier/quick?expense_request_id=${request.id}`} target="_blank" rel="noopener noreferrer">
                                <FileDown className="h-4 w-4" />Demande visa
                            </a>
                        </Button>
                    )}
                    {['valide', 'engage'].includes(request.statut) && (
                        <Button asChild variant="outline" size="sm">
                            <a href={`/rapports/visa_financier/quick?expense_request_id=${request.id}`} target="_blank" rel="noopener noreferrer">
                                <FileDown className="h-4 w-4" />Visa accordé
                            </a>
                        </Button>
                    )}
                    {request.statut === 'rejete' && (
                        <Button asChild variant="outline" size="sm">
                            <a href={`/rapports/notification_rejet/quick?expense_request_id=${request.id}`} target="_blank" rel="noopener noreferrer">
                                <FileDown className="h-4 w-4" />Notification rejet
                            </a>
                        </Button>
                    )}
                    {request.statut === 'retourne_correction' && (
                        <Button asChild variant="outline" size="sm">
                            <a href={`/rapports/notification_retour/quick?expense_request_id=${request.id}`} target="_blank" rel="noopener noreferrer">
                                <FileDown className="h-4 w-4" />Notification retour
                            </a>
                        </Button>
                    )}
                    {request.statut === 'engage' && request.engagement && (
                        <Button asChild variant="outline" size="sm">
                            <a href={`/rapports/bon_engagement/quick?mouvement_id=${request.engagement.id}`} target="_blank" rel="noopener noreferrer">
                                <FileDown className="h-4 w-4" />Bon engagement
                            </a>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow={`${request.numero} — Statut : ${request.statut}`}
                    title={request.objet}
                    description={request.justification}
                />

                <div className="mb-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/expense/requests"><ArrowLeft className="h-4 w-4" />Retour</Link>
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Information</CardTitle></CardHeader>
                        <CardContent className="text-sm space-y-1">
                            <div><span className="text-muted-foreground">Exercice :</span> {request.exercice?.annee}</div>
                            <div><span className="text-muted-foreground">Type :</span> {types_engagement[request.type_engagement] ?? request.type_engagement}</div>
                            <div><span className="text-muted-foreground">Priorité :</span> {request.priorite}</div>
                            <div><span className="text-muted-foreground">Demandeur :</span> {request.demandeur?.name}</div>
                            <div><span className="text-muted-foreground">Département :</span> {request.departement?.libelle ?? '—'}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Montants estimés</CardTitle></CardHeader>
                        <CardContent className="text-sm space-y-1">
                            <div><span className="text-muted-foreground">Total :</span> <strong>{fmt(request.montant_estime)} {request.devise}</strong></div>
                            <div><span className="text-muted-foreground">Part CEEAC :</span> {fmt(request.montant_estime_ceeac)}</div>
                            <div><span className="text-muted-foreground">Part PTF :</span> {fmt(request.montant_estime_ptf)}</div>
                            <div><span className="text-muted-foreground">Source :</span> {request.source_financement?.code ?? '—'}</div>
                            <div><span className="text-muted-foreground">Fournisseur :</span> {request.supplier_pressenti?.libelle ?? '—'}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2"><CardTitle className="text-sm">Calendrier</CardTitle></CardHeader>
                        <CardContent className="text-sm space-y-1">
                            <div><span className="text-muted-foreground">Besoin prévu :</span> {request.date_besoin_prevu ? new Date(request.date_besoin_prevu).toLocaleDateString('fr-FR') : '—'}</div>
                            <div><span className="text-muted-foreground">Livraison souhaitée :</span> {request.date_livraison_souhaitee ? new Date(request.date_livraison_souhaitee).toLocaleDateString('fr-FR') : '—'}</div>
                            <div><span className="text-muted-foreground">Validé par :</span> {request.valideur_hierarchique?.name ?? '—'}</div>
                            <div><span className="text-muted-foreground">Validé le :</span> {request.valide_at ? new Date(request.valide_at).toLocaleString('fr-FR') : '—'}</div>
                        </CardContent>
                    </Card>
                </div>

                {request.description_detaillee && (
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Description détaillée</CardTitle></CardHeader>
                        <CardContent><p className="whitespace-pre-line text-sm">{request.description_detaillee}</p></CardContent>
                    </Card>
                )}

                {request.motif_decision && (
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Motif de décision</CardTitle></CardHeader>
                        <CardContent><p className="whitespace-pre-line text-sm">{request.motif_decision}</p></CardContent>
                    </Card>
                )}

                {request.engagement && (
                    <Card className="border-emerald-200/60 bg-emerald-50/30">
                        <CardHeader><CardTitle className="text-sm flex items-center gap-2"><CheckCircle2 className="h-4 w-4 text-emerald-600" />Engagement créé</CardTitle></CardHeader>
                        <CardContent className="text-sm space-y-1">
                            <div><span className="text-muted-foreground">Référence :</span> <code>{request.engagement.reference}</code></div>
                            <div><span className="text-muted-foreground">Montant :</span> {fmt(request.engagement.montant)} {request.devise}</div>
                            <div><span className="text-muted-foreground">Date :</span> {new Date(request.engagement.date_mouvement).toLocaleDateString('fr-FR')}</div>
                            <div><span className="text-muted-foreground">Statut :</span> {request.engagement.statut_mouvement}</div>
                        </CardContent>
                    </Card>
                )}

                {/* === ACTIONS === */}
                <Card>
                    <CardHeader><CardTitle>Actions disponibles</CardTitle></CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {can.submit && (
                            <Button onClick={() => submit('submit')}>
                                <Send className="h-4 w-4" />Soumettre pour validation
                            </Button>
                        )}
                        {can.validate && (
                            <form onSubmit={submitValidate} className="inline-flex items-center gap-2">
                                <Button type="submit" variant="default">
                                    <CheckCircle2 className="h-4 w-4" />Valider
                                </Button>
                                <Button type="button" variant="outline" onClick={() => setShowMotif('return')}>
                                    Retour pour correction
                                </Button>
                            </form>
                        )}
                        {can.reject && (
                            <Button variant="destructive" onClick={() => setShowMotif('reject')}>
                                <X className="h-4 w-4" />Rejeter
                            </Button>
                        )}
                        {can.cancel && (
                            <Button variant="outline" onClick={() => setShowMotif('cancel')}>
                                Annuler
                            </Button>
                        )}
                    </CardContent>
                </Card>

                {showMotif && (
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Motif {showMotif === 'reject' ? 'de rejet' : showMotif === 'return' ? 'de retour' : 'd\'annulation'}</CardTitle></CardHeader>
                        <form onSubmit={submitMotif(showMotif)}>
                            <CardContent>
                                <Textarea rows={4} value={motifForm.data.motif} onChange={(e) => motifForm.setData('motif', e.target.value)} required placeholder="Justifiez votre décision…" />
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button type="button" variant="outline" onClick={() => setShowMotif(null)}>Annuler</Button>
                                <Button type="submit" disabled={motifForm.processing}>
                                    {motifForm.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Confirmer
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                {can.engage && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Engager budgétairement</CardTitle>
                            <p className="text-sm text-muted-foreground">Crée un mouvement budgétaire type engagement. Imputations multiples possibles.</p>
                        </CardHeader>
                        <form onSubmit={submitEngager}>
                            <CardContent className="space-y-3">
                                {engagerForm.data.imputations.map((imp: any, idx: number) => (
                                    <div key={idx} className="grid grid-cols-1 sm:grid-cols-4 gap-2 p-3 border rounded-md bg-muted/30">
                                        <div>
                                            <label className="text-xs text-muted-foreground">Ligne budgétaire (ID)</label>
                                            <input type="number" required className="w-full rounded-md border px-3 py-1.5 text-sm"
                                                value={imp.budget_ligne_id}
                                                onChange={(e) => {
                                                    const arr = [...engagerForm.data.imputations];
                                                    arr[idx].budget_ligne_id = Number(e.target.value);
                                                    engagerForm.setData('imputations', arr);
                                                }} />
                                        </div>
                                        <div>
                                            <label className="text-xs text-muted-foreground">Libellé</label>
                                            <input type="text" className="w-full rounded-md border px-3 py-1.5 text-sm"
                                                value={imp.libelle}
                                                onChange={(e) => {
                                                    const arr = [...engagerForm.data.imputations];
                                                    arr[idx].libelle = e.target.value;
                                                    engagerForm.setData('imputations', arr);
                                                }} />
                                        </div>
                                        <div>
                                            <label className="text-xs text-muted-foreground">Montant</label>
                                            <input type="number" step="0.01" required className="w-full rounded-md border px-3 py-1.5 text-sm tabular-nums"
                                                value={imp.montant}
                                                onChange={(e) => {
                                                    const arr = [...engagerForm.data.imputations];
                                                    arr[idx].montant = Number(e.target.value);
                                                    engagerForm.setData('imputations', arr);
                                                }} />
                                        </div>
                                        <div className="flex items-end">
                                            {engagerForm.data.imputations.length > 1 && (
                                                <Button type="button" variant="ghost" size="sm" onClick={() => {
                                                    const arr = engagerForm.data.imputations.filter((_: any, i: number) => i !== idx);
                                                    engagerForm.setData('imputations', arr);
                                                }}>
                                                    Retirer
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                <Button type="button" variant="outline" size="sm" onClick={() => {
                                    engagerForm.setData('imputations', [...engagerForm.data.imputations, { budget_ligne_id: '', libelle: '', montant: 0 }]);
                                }}>
                                    + Ajouter une imputation
                                </Button>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button type="submit" disabled={engagerForm.processing}>
                                    {engagerForm.processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Engager le budget
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
