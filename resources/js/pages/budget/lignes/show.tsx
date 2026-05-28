import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Banknote, CheckCircle2, FileCheck2, Pencil, Send } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatCurrency, formatDate, formatPercent } from '@/lib/utils';

export default function LigneShow({ ligne, can }: any) {
    const [showEngager, setShowEngager] = useState(false);
    const engagerForm = useForm({
        montant: '',
        beneficiaire_nom: '',
        beneficiaire_reference: '',
        numero_piece: '',
        motif: '',
    });

    const engager = (e: FormEvent) => {
        e.preventDefault();
        engagerForm.post(`/budget/lignes/${ligne.id}/engager`, {
            onSuccess: () => {
                engagerForm.reset();
                setShowEngager(false);
            },
        });
    };

    const transitionner = (mvtId: number, action: string) => {
        if (action === 'liquider') {
            const montant = window.prompt('Montant à liquider :', '');
            if (!montant) return;
            router.post(`/budget/mouvements/${mvtId}/${action}`, { montant }, { preserveScroll: true });
        } else if (action === 'payer') {
            const mode = window.prompt('Mode de paiement (virement/cheque/especes/mobile_money/autre) :', 'virement');
            if (!mode) return;
            router.post(`/budget/mouvements/${mvtId}/${action}`, { mode_paiement: mode }, { preserveScroll: true });
        } else {
            router.post(`/budget/mouvements/${mvtId}/${action}`, {}, { preserveScroll: true });
        }
    };

    return (
        <AppLayout
            pageTitle={ligne.libelle}
            breadcrumbs={[{ label: 'Budget', href: '/budget' }, { label: 'Lignes', href: '/budget/lignes' }, { label: ligne.code_action ?? '#' + ligne.id }]}
            actions={
                <div className="flex gap-2">
                    <Button variant="ghost" size="sm" asChild><Link href="/budget/lignes"><ArrowLeft className="h-4 w-4" />Retour</Link></Button>
                    {can.update && (<Button variant="outline" size="sm" asChild><Link href={`/budget/lignes/${ligne.id}/edit`}><Pencil className="h-4 w-4" />Modifier</Link></Button>)}
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Détail ligne budgétaire"
                    description="Consultation de la nomenclature, des montants et rattachements RBM."
                />
            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <div className="mb-2 flex flex-wrap items-center gap-2">
                                    <RbmStatusBadge statut={ligne.statut} />
                                    <Badge variant="outline">{ligne.nature === 'recette' ? 'Recette' : 'Dépense'}</Badge>
                                    <Badge variant="secondary">{ligne.type_budget}</Badge>
                                    {ligne.pilier && <Badge variant="default">Pilier {ligne.pilier}</Badge>}
                                </div>
                                <CardTitle className="text-xl">{ligne.libelle}</CardTitle>
                                {ligne.description && <p className="mt-2 text-sm text-muted-foreground">{ligne.description}</p>}
                            </div>
                            <div className="text-right">
                                <p className="text-xs text-muted-foreground">Montant total</p>
                                <p className="text-2xl font-bold tabular-nums">{formatCurrency(ligne.montant_total)}</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="border-t pt-4 space-y-4">
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-5 text-sm">
                            <Meta label="Titre" value={ligne.titre_code ?? '—'} />
                            <Meta label="Chapitre" value={ligne.chapitre_code ?? '—'} />
                            <Meta label="Article" value={ligne.article_code ?? '—'} />
                            <Meta label="Paragraphe" value={ligne.paragraphe_code ?? '—'} />
                            <Meta label="Code action" value={ligne.code_action ?? '—'} />
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle className="text-base">Financement</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <KV label="Part CEEAC-EM" value={ligne.montant_ceeac_em} />
                            <KV label="Part PTF" value={ligne.montant_ptf} />
                            <div className="border-t pt-2" />
                            <KV label="Total" value={ligne.montant_total} bold />
                            <div className="border-t pt-2" />
                            <Meta label="Source de financement" value={ligne.source ? `${ligne.source.code} — ${ligne.source.libelle}` : '—'} />
                            {ligne.partenaire && <Meta label="Partenaire" value={`${ligne.partenaire.code} — ${ligne.partenaire.libelle}`} />}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-base">Exécution</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <KV label="Engagé" value={ligne.montant_engage} />
                            <KV label="Liquidé" value={ligne.montant_liquide} />
                            <KV label="Ordonnancé" value={ligne.montant_ordonnance} />
                            <KV label="Payé" value={ligne.montant_paye} />
                            <KV label="Disponible" value={ligne.montant_disponible} bold />
                            <div className="space-y-1.5 pt-2">
                                <div className="flex justify-between text-xs">
                                    <span>Taux de consommation</span>
                                    <span className="tabular-nums">{formatPercent(ligne.taux_consommation)}</span>
                                </div>
                                <Progress value={ligne.taux_consommation} />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader><CardTitle className="text-base">Rattachement RBM & Comparatif N-1</CardTitle></CardHeader>
                    <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                        <div className="space-y-2">
                            {ligne.axe && <Meta label="Axe" value={`${ligne.axe.code} — ${ligne.axe.libelle}`} />}
                            {ligne.produit && <Meta label="Produit" value={`${ligne.produit.code} — ${ligne.produit.libelle}`} />}
                            {ligne.sous_produit && <Meta label="Sous-Produit" value={`${ligne.sous_produit.code} — ${ligne.sous_produit.libelle}`} />}
                            {ligne.activite && <Meta label="Activité" value={`${ligne.activite.code} — ${ligne.activite.libelle}`} />}
                            {ligne.tache && <Meta label="Tâche" value={`${ligne.tache.code} — ${ligne.tache.libelle}`} />}
                            {ligne.departement && <Meta label="Département" value={`${ligne.departement.code} — ${ligne.departement.libelle}`} />}
                            {ligne.direction && <Meta label="Direction" value={`${ligne.direction.code} — ${ligne.direction.libelle}`} />}
                        </div>
                        <div className="space-y-2">
                            <KV label="Budget année précédente" value={ligne.budget_annee_precedente} />
                            <KV label="Réalisation année précédente" value={ligne.realisation_annee_precedente} />
                            {ligne.taux_realisation_precedent !== null && <KV label="Taux de réalisation N-1" value={`${ligne.taux_realisation_precedent}%`} raw />}
                            {ligne.variation !== null && <KV label="Variation N-1 → N" value={`${ligne.variation}%`} raw />}
                            {ligne.observations && (
                                <div className="rounded-md border bg-muted/30 p-3 mt-2">
                                    <p className="text-xs font-semibold uppercase">Observations</p>
                                    <p className="text-xs text-muted-foreground mt-1">{ligne.observations}</p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* ===== Cycle d'exécution IPSAS ===== */}
                {can?.engager_budget && (
                    <Card className="border-emerald-200/60">
                        <CardHeader>
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Banknote className="h-5 w-5 text-emerald-700" />
                                        Cycle d'exécution comptable IPSAS
                                    </CardTitle>
                                    <CardDescription>
                                        Engagement → Liquidation → Ordonnancement → Paiement · Conforme IPSAS 24
                                    </CardDescription>
                                </div>
                                <Button size="sm" onClick={() => setShowEngager(!showEngager)}>
                                    {showEngager ? 'Annuler' : 'Nouvel engagement'}
                                </Button>
                            </div>
                        </CardHeader>
                        {showEngager && (
                            <CardContent>
                                <form onSubmit={engager} className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <div className="space-y-1">
                                        <Label htmlFor="montant">Montant (FCFA) *</Label>
                                        <Input
                                            id="montant"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value={engagerForm.data.montant}
                                            onChange={(e) => engagerForm.setData('montant', e.target.value)}
                                            placeholder={`Disponible : ${formatCurrency(ligne.montant_disponible)}`}
                                            required
                                        />
                                        {engagerForm.errors.montant && <p className="text-xs text-destructive">{engagerForm.errors.montant}</p>}
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="numero_piece">N° pièce (bon de commande)</Label>
                                        <Input
                                            id="numero_piece"
                                            value={engagerForm.data.numero_piece}
                                            onChange={(e) => engagerForm.setData('numero_piece', e.target.value)}
                                            placeholder="BC-2027-001"
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="beneficiaire_nom">Bénéficiaire</Label>
                                        <Input
                                            id="beneficiaire_nom"
                                            value={engagerForm.data.beneficiaire_nom}
                                            onChange={(e) => engagerForm.setData('beneficiaire_nom', e.target.value)}
                                            placeholder="Fournisseur, partenaire…"
                                        />
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="beneficiaire_reference">Référence bénéficiaire (NIF, RCCM)</Label>
                                        <Input
                                            id="beneficiaire_reference"
                                            value={engagerForm.data.beneficiaire_reference}
                                            onChange={(e) => engagerForm.setData('beneficiaire_reference', e.target.value)}
                                        />
                                    </div>
                                    <div className="md:col-span-2 space-y-1">
                                        <Label htmlFor="motif">Motif</Label>
                                        <Textarea
                                            id="motif"
                                            rows={2}
                                            value={engagerForm.data.motif}
                                            onChange={(e) => engagerForm.setData('motif', e.target.value)}
                                        />
                                    </div>
                                    {engagerForm.errors.cycle && (
                                        <p className="md:col-span-2 rounded-md bg-destructive/10 p-2 text-xs text-destructive">
                                            {engagerForm.errors.cycle}
                                        </p>
                                    )}
                                    <div className="md:col-span-2 flex justify-end">
                                        <Button type="submit" disabled={engagerForm.processing}>
                                            <Send className="h-4 w-4" />
                                            Engager
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        )}
                    </Card>
                )}

                {ligne.mouvements?.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Mouvements ({ligne.mouvements.length})</CardTitle>
                            <CardDescription>Chaîne IPSAS · Actions disponibles selon vos permissions</CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead className="text-right">Montant</TableHead>
                                        <TableHead>Pièce</TableHead>
                                        <TableHead>Bénéficiaire</TableHead>
                                        <TableHead>Acteur</TableHead>
                                        <TableHead className="text-right">Cycle suivant</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {ligne.mouvements.map((m: any) => {
                                        const actionSuivante = nextAction(m.type, m.suivants);

                                        return (
                                            <TableRow key={m.id}>
                                                <TableCell className="text-xs">{formatDate(m.date_mouvement)}</TableCell>
                                                <TableCell>
                                                    <Badge variant={typeBadgeVariant(m.type)}>{m.type}</Badge>
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">{formatCurrency(m.montant)}</TableCell>
                                                <TableCell className="text-xs">{m.numero_piece ?? m.reference ?? '—'}</TableCell>
                                                <TableCell className="text-xs">
                                                    {m.beneficiaire_nom ?? '—'}
                                                    {m.beneficiaire_reference && <span className="block text-[10px] text-muted-foreground">{m.beneficiaire_reference}</span>}
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    {m.ordonnateur?.name ?? m.comptable?.name ?? m.saisi_par?.name ?? '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {actionSuivante && can?.[`${actionSuivante}_budget`] !== false && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => transitionner(m.id, actionSuivante)}
                                                        >
                                                            {actionLabel(actionSuivante)}
                                                        </Button>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
                </div>
</AppLayout>
    );
}

function Meta({ label, value }: any) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-sm font-medium">{value}</p>
        </div>
    );
}

function KV({ label, value, bold, raw }: any) {
    return (
        <div className="flex justify-between">
            <span className={bold ? 'font-semibold' : ''}>{label}</span>
            <span className={`tabular-nums ${bold ? 'font-bold' : ''}`}>{raw ? value : formatCurrency(value)}</span>
        </div>
    );
}

function nextAction(type: string, suivants: any[]): string | null {
    const order = ['engagement', 'liquidation', 'ordonnancement', 'paiement'];
    const idx = order.indexOf(type);
    if (idx === -1 || idx === order.length - 1) return null;
    const next = order[idx + 1];
    // Si un suivant valide existe déjà, plus rien à faire
    if (suivants?.some((s: any) => s.type === next && s.statut_mouvement === 'valide')) return null;

    return ({ engagement: 'liquider', liquidation: 'ordonnancer', ordonnancement: 'payer' } as Record<string, string>)[type] ?? null;
}

function actionLabel(action: string): string {
    return ({
        liquider: 'Liquider',
        ordonnancer: 'Ordonnancer',
        payer: 'Payer',
    } as Record<string, string>)[action] ?? action;
}

function typeBadgeVariant(type: string): 'default' | 'secondary' | 'success' | 'warning' | 'destructive' | 'outline' {
    return ({
        engagement: 'warning',
        liquidation: 'secondary',
        ordonnancement: 'default',
        paiement: 'success',
        desengagement: 'destructive',
        ajustement: 'outline',
        transfert: 'outline',
    } as any)[type] ?? 'outline';
}
