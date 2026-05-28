import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FileText, GitBranch, Layers3, LoaderCircle, PiggyBank, Save, Wallet } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

export default function LigneForm({ mode, ligne, exercice_id_defaut, exercices, sources, axes, produits, sous_produits, activites, taches, departements, directions }: any) {
    const { data, setData, post, put, processing, errors } = useForm({
        exercice_id: ligne?.exercice_id ?? exercice_id_defaut ?? exercices[0]?.id ?? '',
        titre_code: ligne?.titre_code ?? '',
        chapitre_code: ligne?.chapitre_code ?? '',
        article_code: ligne?.article_code ?? '',
        paragraphe_code: ligne?.paragraphe_code ?? '',
        code_action: ligne?.code_action ?? '',
        libelle: ligne?.libelle ?? '',
        description: ligne?.description ?? '',
        nature: ligne?.nature ?? 'depense',
        type_budget: ligne?.type_budget ?? 'fonctionnement',
        pilier: ligne?.pilier ?? '',
        montant_total: ligne?.montant_total ?? 0,
        montant_ceeac_em: ligne?.montant_ceeac_em ?? 0,
        montant_ptf: ligne?.montant_ptf ?? 0,
        budget_annee_precedente: ligne?.budget_annee_precedente ?? 0,
        realisation_annee_precedente: ligne?.realisation_annee_precedente ?? 0,
        source_financement_id: ligne?.source_financement_id ?? '',
        axe_id: ligne?.axe_id ?? '',
        produit_id: ligne?.produit_id ?? '',
        sous_produit_id: ligne?.sous_produit_id ?? '',
        activite_id: ligne?.activite_id ?? '',
        tache_id: ligne?.tache_id ?? '',
        departement_id: ligne?.departement_id ?? '',
        direction_id: ligne?.direction_id ?? '',
        observations: ligne?.observations ?? '',
    });

    const total = (Number(data.montant_ceeac_em) || 0) + (Number(data.montant_ptf) || 0);
    const ecart = Math.abs(total - (Number(data.montant_total) || 0));
    const pageTitle = mode === 'create' ? 'Nouvelle ligne budgétaire' : `Modifier ${ligne?.code_action ?? 'la ligne'}`;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (mode === 'create') post('/budget/lignes');
        else put(`/budget/lignes/${ligne.id}`);
    };

    return (
        <AppLayout
            pageTitle={pageTitle}
            breadcrumbs={[{ label: 'Budget', href: '/budget' }, { label: 'Lignes', href: '/budget/lignes' }, { label: mode === 'create' ? 'Création' : 'Édition' }]}
            actions={
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/budget/lignes"><ArrowLeft className="h-4 w-4" />Retour</Link>
                </Button>
            }
        >
            <form onSubmit={submit} className="space-y-6">
                <InstitutionalHero
                    eyebrow="Pilotage budgétaire"
                    title={mode === 'create' ? 'Créer une ligne budgétaire' : 'Modifier une ligne budgétaire'}
                    description="Saisie structurée de la nomenclature budgétaire, des sources de financement et du rattachement RBM."
                    metrics={[
                        { icon: Wallet, label: 'Exercices', value: Number(exercices.length ?? 0).toLocaleString('fr-FR') },
                        { icon: PiggyBank, label: 'Sources', value: Number(sources.length ?? 0).toLocaleString('fr-FR') },
                        { icon: GitBranch, label: 'Axes RBM', value: Number(axes.length ?? 0).toLocaleString('fr-FR') },
                        { icon: FileText, label: 'Total saisi', value: Number(data.montant_total ?? 0).toLocaleString('fr-FR') },
                    ]}
                    footer="Nomenclature : Titre → Chapitre → Article → Paragraphe → Code action"
                    footerIcon={Layers3}
                />

                <div className="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_340px]">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Cadre budgétaire</CardTitle>
                                <CardDescription>Exercice, nature et classification de la ligne.</CardDescription>
                            </CardHeader>
                            <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <Field label="Exercice *" error={errors.exercice_id}>
                                    <Select value={String(data.exercice_id)} onValueChange={(v) => setData('exercice_id', Number(v))}>
                                        <SelectTrigger><SelectValue placeholder="Choisir" /></SelectTrigger>
                                        <SelectContent>
                                            {exercices.map((e: any) => (<SelectItem key={e.id} value={String(e.id)}>{e.annee} - {e.libelle}</SelectItem>))}
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field label="Nature *">
                                    <Select value={data.nature} onValueChange={(v) => setData('nature', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="recette">Recette</SelectItem>
                                            <SelectItem value="depense">Dépense</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </Field>
                                <Field label="Type de budget *">
                                    <Select value={data.type_budget} onValueChange={(v) => setData('type_budget', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="recette_interne">Recette interne</SelectItem>
                                            <SelectItem value="recette_externe">Recette externe</SelectItem>
                                            <SelectItem value="fonctionnement">Fonctionnement</SelectItem>
                                            <SelectItem value="investissement">Investissement</SelectItem>
                                            <SelectItem value="equipement">Équipement</SelectItem>
                                            <SelectItem value="dotation">Dotation</SelectItem>
                                            <SelectItem value="dette">Dette</SelectItem>
                                            <SelectItem value="transfert">Transfert</SelectItem>
                                            <SelectItem value="autre">Autre</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </Field>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Nomenclature et libellé</CardTitle>
                                <CardDescription>Codification CEEAC et description fonctionnelle de la ligne.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-5">
                                    <Field label="Titre"><Input value={data.titre_code} onChange={(e) => setData('titre_code', e.target.value)} placeholder="Titre 1" /></Field>
                                    <Field label="Chapitre"><Input value={data.chapitre_code} onChange={(e) => setData('chapitre_code', e.target.value)} placeholder="66" /></Field>
                                    <Field label="Article"><Input value={data.article_code} onChange={(e) => setData('article_code', e.target.value)} placeholder="661" /></Field>
                                    <Field label="Paragraphe"><Input value={data.paragraphe_code} onChange={(e) => setData('paragraphe_code', e.target.value)} placeholder="6610" /></Field>
                                    <Field label="Code action"><Input value={data.code_action} onChange={(e) => setData('code_action', e.target.value)} placeholder="66101" /></Field>
                                </div>
                                <Field label="Libellé *" error={errors.libelle}>
                                    <Input value={data.libelle} onChange={(e) => setData('libelle', e.target.value)} required />
                                </Field>
                                <Field label="Description">
                                    <Textarea rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} />
                                </Field>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Montants et financement</CardTitle>
                                <CardDescription>Répartition CEEAC-EM, PTF et contrôle de cohérence du total.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <Field label="Montant CEEAC-EM *">
                                        <Input type="number" step="0.01" value={data.montant_ceeac_em} onChange={(e) => setData('montant_ceeac_em', Number(e.target.value))} />
                                    </Field>
                                    <Field label="Montant PTF *">
                                        <Input type="number" step="0.01" value={data.montant_ptf} onChange={(e) => setData('montant_ptf', Number(e.target.value))} />
                                    </Field>
                                    <Field label="Total *" error={errors.montant_total}>
                                        <Input type="number" step="0.01" value={data.montant_total} onChange={(e) => setData('montant_total', Number(e.target.value))} className="font-semibold" />
                                        {ecart > 0.01 && (
                                            <p className="text-xs text-warning-foreground">Suggéré : {total.toLocaleString('fr-FR')} (CEEAC + PTF)</p>
                                        )}
                                    </Field>
                                </div>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <Field label="Budget année précédente">
                                        <Input type="number" step="0.01" value={data.budget_annee_precedente} onChange={(e) => setData('budget_annee_precedente', Number(e.target.value))} />
                                    </Field>
                                    <Field label="Réalisation année précédente">
                                        <Input type="number" step="0.01" value={data.realisation_annee_precedente} onChange={(e) => setData('realisation_annee_precedente', Number(e.target.value))} />
                                    </Field>
                                    <Field label="Source de financement">
                                        <Select value={String(data.source_financement_id)} onValueChange={(v) => setData('source_financement_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {sources.map((s: any) => (<SelectItem key={s.id} value={String(s.id)}>{s.code} - {s.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Rattachement RBM et organisation</CardTitle>
                                <CardDescription>Association optionnelle à la chaîne RBM/GAR et aux unités responsables.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <Field label="Axe RBM">
                                        <Select value={String(data.axe_id)} onValueChange={(v) => setData('axe_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {axes.map((a: any) => (<SelectItem key={a.id} value={String(a.id)}>{a.code} - {a.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field label="Produit">
                                        <Select value={String(data.produit_id)} onValueChange={(v) => setData('produit_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {produits.map((p: any) => (<SelectItem key={p.id} value={String(p.id)}>{p.code} - {p.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field label="Sous-produit">
                                        <Select value={String(data.sous_produit_id)} onValueChange={(v) => setData('sous_produit_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {sous_produits.map((sp: any) => (<SelectItem key={sp.id} value={String(sp.id)}>{sp.code} - {sp.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field label="Activité">
                                        <Select value={String(data.activite_id)} onValueChange={(v) => setData('activite_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {activites.map((a: any) => (<SelectItem key={a.id} value={String(a.id)}>{a.code} - {a.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field label="Tâche">
                                        <Select value={String(data.tache_id)} onValueChange={(v) => setData('tache_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {taches.map((t: any) => (<SelectItem key={t.id} value={String(t.id)}>{t.code} - {t.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field label="Pilier PAPA">
                                        <Input type="number" min={1} max={10} value={data.pilier} onChange={(e) => setData('pilier', e.target.value ? Number(e.target.value) : '' as any)} />
                                    </Field>
                                    <Field label="Département">
                                        <Select value={String(data.departement_id)} onValueChange={(v) => setData('departement_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {departements.map((d: any) => (<SelectItem key={d.id} value={String(d.id)}>{d.code} - {d.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                    <Field label="Direction">
                                        <Select value={String(data.direction_id)} onValueChange={(v) => setData('direction_id', Number(v))}>
                                            <SelectTrigger><SelectValue placeholder="Sélectionner" /></SelectTrigger>
                                            <SelectContent>
                                                {directions.map((d: any) => (<SelectItem key={d.id} value={String(d.id)}>{d.code} - {d.libelle}</SelectItem>))}
                                            </SelectContent>
                                        </Select>
                                    </Field>
                                </div>
                                <Field label="Observations">
                                    <Textarea rows={3} value={data.observations} onChange={(e) => setData('observations', e.target.value)} />
                                </Field>
                            </CardContent>
                        </Card>
                    </div>

                    <aside className="space-y-4">
                        <Card className="border-primary/30">
                            <CardHeader>
                                <CardTitle className="text-base">Contrôle</CardTitle>
                                <CardDescription>Validation rapide avant enregistrement.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <ControlLine label="CEEAC-EM" value={Number(data.montant_ceeac_em ?? 0).toLocaleString('fr-FR')} />
                                <ControlLine label="PTF" value={Number(data.montant_ptf ?? 0).toLocaleString('fr-FR')} />
                                <ControlLine label="Total calculé" value={total.toLocaleString('fr-FR')} />
                                <ControlLine label="Total saisi" value={Number(data.montant_total ?? 0).toLocaleString('fr-FR')} strong />
                                {ecart > 0.01 && <p className="rounded-md border border-warning/30 bg-warning/10 p-2 text-xs text-warning-foreground">Le total saisi doit correspondre à CEEAC-EM + PTF.</p>}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="flex flex-col gap-2 p-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                                    {mode === 'create' ? 'Créer la ligne' : 'Enregistrer'}
                                </Button>
                                <Button variant="outline" type="button" asChild><Link href="/budget/lignes">Annuler</Link></Button>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </form>
        </AppLayout>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="space-y-2">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

function ControlLine({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <span className="text-muted-foreground">{label}</span>
            <span className={strong ? 'font-semibold tabular-nums' : 'tabular-nums'}>{value}</span>
        </div>
    );
}
