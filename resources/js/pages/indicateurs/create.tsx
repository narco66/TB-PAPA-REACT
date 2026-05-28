import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BarChart3, LoaderCircle, Target } from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    sousProduits: Array<{ id: number; code: string; libelle: string; parent: string }>;
    responsables: Array<{ id: number; name: string; fonction: string | null }>;
    options: {
        categories: Record<string, string>;
        polarites: Record<string, string>;
        frequences: string[];
        types: string[];
    };
}

export default function IndicateurCreate({ sousProduits, responsables, options }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        sous_produit_id: '' as number | '',
        code: '',
        libelle: '',
        definition: '',
        type: 'quantitatif',
        categorie: 'produit',
        polarite: 'positive',
        unite: '%',
        baseline: '',
        cible: '100',
        date_baseline: '',
        palier_t1: '',
        palier_t2: '',
        palier_t3: '',
        palier_t4: '',
        seuil_alerte_bas: '',
        seuil_alerte_haut: '',
        methode_calcul: '',
        hypotheses: '',
        risques_associes: '',
        frequence_collecte: 'trimestrielle',
        source_donnees: '',
        instrument_collecte: '',
        responsable_id: '' as number | '',
        responsable_collecte_id: '' as number | '',
        desagregation_genre: false,
        desagregation_geographique: false,
        desagregation_vulnerabilite: false,
        desagregation_age: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/indicateurs');
    };

    return (
        <AppLayout
            pageTitle="Nouvel indicateur (CMR)"
            breadcrumbs={[
                { label: 'Indicateurs', href: '/indicateurs' },
                { label: 'Création' },
            ]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Cadre de Mesure du Rendement"
                    title="Créer un indicateur CMR"
                    description="Définition complète conforme aux standards bailleurs : typologie OCDE, paliers, désagrégation, méthodologie."
                    footer="Conforme RBM/GAR — BAD · Banque Mondiale · UE · Union Africaine"
                    footerIcon={BarChart3}
                />

                <div>
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/indicateurs">
                            <ArrowLeft className="h-4 w-4" />
                            Retour
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* === Section 1 : Identification === */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Target className="h-5 w-5" />
                                1. Identification et rattachement RBM
                            </CardTitle>
                            <CardDescription>Code unique, libellé, rattachement au Sous-Produit.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label>Sous-Produit de rattachement *</Label>
                                <Select
                                    value={String(data.sous_produit_id)}
                                    onValueChange={(v) => setData('sous_produit_id', Number(v))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choisir un Sous-Produit" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {sousProduits.map((sp) => (
                                            <SelectItem key={sp.id} value={String(sp.id)}>
                                                {sp.code} — {sp.libelle} ({sp.parent})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.sous_produit_id && <p className="text-xs text-destructive">{errors.sous_produit_id}</p>}
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="code">Code *</Label>
                                    <Input
                                        id="code"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                        placeholder="IND-001"
                                        required
                                    />
                                    {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                                </div>
                                <div className="space-y-2 sm:col-span-2">
                                    <Label htmlFor="libelle">Libellé *</Label>
                                    <Input
                                        id="libelle"
                                        value={data.libelle}
                                        onChange={(e) => setData('libelle', e.target.value)}
                                        required
                                    />
                                    {errors.libelle && <p className="text-xs text-destructive">{errors.libelle}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="definition">Définition opérationnelle</Label>
                                <Textarea
                                    id="definition"
                                    rows={3}
                                    value={data.definition}
                                    onChange={(e) => setData('definition', e.target.value)}
                                    placeholder="Que mesure précisément cet indicateur ?"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* === Section 2 : Typologie CAD/OCDE === */}
                    <Card>
                        <CardHeader>
                            <CardTitle>2. Typologie OCDE/CAD</CardTitle>
                            <CardDescription>Niveau de résultat dans la chaîne de causalité (RBM).</CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="space-y-2">
                                <Label>Catégorie OCDE *</Label>
                                <Select value={data.categorie} onValueChange={(v) => setData('categorie', v)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(options.categories).map(([k, v]) => (
                                            <SelectItem key={k} value={k}>{v}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Type *</Label>
                                <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.types.map((t) => (
                                            <SelectItem key={t} value={t}>
                                                {t.charAt(0).toUpperCase() + t.slice(1)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Polarité</Label>
                                <Select value={data.polarite} onValueChange={(v) => setData('polarite', v)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(options.polarites).map(([k, v]) => (
                                            <SelectItem key={k} value={k}>{v}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="unite">Unité de mesure</Label>
                                <Input
                                    id="unite"
                                    value={data.unite}
                                    onChange={(e) => setData('unite', e.target.value)}
                                    placeholder="%, FCFA, jours…"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* === Section 3 : Baseline, cible, paliers trimestriels === */}
                    <Card>
                        <CardHeader>
                            <CardTitle>3. Baseline, cible et paliers trimestriels</CardTitle>
                            <CardDescription>Définition de la trajectoire attendue sur l'exercice.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="baseline">Baseline (référence initiale)</Label>
                                    <Input
                                        id="baseline"
                                        type="number"
                                        step="0.0001"
                                        value={data.baseline}
                                        onChange={(e) => setData('baseline', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="cible">Cible annuelle *</Label>
                                    <Input
                                        id="cible"
                                        type="number"
                                        step="0.0001"
                                        value={data.cible}
                                        onChange={(e) => setData('cible', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="date_baseline">Date de baseline</Label>
                                    <Input
                                        id="date_baseline"
                                        type="date"
                                        value={data.date_baseline}
                                        onChange={(e) => setData('date_baseline', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Paliers trimestriels (cibles intermédiaires)
                                </p>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {(['t1', 't2', 't3', 't4'] as const).map((t) => (
                                        <div key={t} className="space-y-1">
                                            <Label htmlFor={`palier_${t}`} className="text-xs">
                                                Palier {t.toUpperCase()}
                                            </Label>
                                            <Input
                                                id={`palier_${t}`}
                                                type="number"
                                                step="0.0001"
                                                value={data[`palier_${t}` as 'palier_t1']}
                                                onChange={(e) => setData(`palier_${t}` as 'palier_t1', e.target.value)}
                                            />
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="seuil_alerte_bas">Seuil d'alerte bas</Label>
                                    <Input
                                        id="seuil_alerte_bas"
                                        type="number"
                                        step="0.0001"
                                        value={data.seuil_alerte_bas}
                                        onChange={(e) => setData('seuil_alerte_bas', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="seuil_alerte_haut">Seuil d'alerte haut</Label>
                                    <Input
                                        id="seuil_alerte_haut"
                                        type="number"
                                        step="0.0001"
                                        value={data.seuil_alerte_haut}
                                        onChange={(e) => setData('seuil_alerte_haut', e.target.value)}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* === Section 4 : Méthodologie === */}
                    <Card>
                        <CardHeader>
                            <CardTitle>4. Méthodologie de mesure</CardTitle>
                            <CardDescription>Méthode, source, fréquence, instrument et responsables.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="methode_calcul">Méthode de calcul / formule</Label>
                                <Textarea
                                    id="methode_calcul"
                                    rows={2}
                                    value={data.methode_calcul}
                                    onChange={(e) => setData('methode_calcul', e.target.value)}
                                    placeholder="Formule, étapes de calcul, dénominateur, numérateur…"
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Fréquence de collecte *</Label>
                                    <Select value={data.frequence_collecte} onValueChange={(v) => setData('frequence_collecte', v)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.frequences.map((f) => (
                                                <SelectItem key={f} value={f}>
                                                    {f.charAt(0).toUpperCase() + f.slice(1)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="instrument_collecte">Instrument de collecte</Label>
                                    <Input
                                        id="instrument_collecte"
                                        value={data.instrument_collecte}
                                        onChange={(e) => setData('instrument_collecte', e.target.value)}
                                        placeholder="Enquête, base administrative, observation…"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="source_donnees">Source des données</Label>
                                <Input
                                    id="source_donnees"
                                    value={data.source_donnees}
                                    onChange={(e) => setData('source_donnees', e.target.value)}
                                    placeholder="Système, rapport officiel, partenaire…"
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Responsable de collecte</Label>
                                    <Select
                                        value={String(data.responsable_collecte_id)}
                                        onValueChange={(v) => setData('responsable_collecte_id', Number(v))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Désigner" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {responsables.map((r) => (
                                                <SelectItem key={r.id} value={String(r.id)}>
                                                    {r.name}
                                                    {r.fonction ? ` — ${r.fonction}` : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Responsable de validation</Label>
                                    <Select
                                        value={String(data.responsable_id)}
                                        onValueChange={(v) => setData('responsable_id', Number(v))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Désigner" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {responsables.map((r) => (
                                                <SelectItem key={r.id} value={String(r.id)}>
                                                    {r.name}
                                                    {r.fonction ? ` — ${r.fonction}` : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* === Section 5 : Désagrégation === */}
                    <Card>
                        <CardHeader>
                            <CardTitle>5. Désagrégation des données</CardTitle>
                            <CardDescription>Dimensions à ventiler lors de la saisie des valeurs (exigences bailleurs).</CardDescription>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            {(
                                [
                                    ['desagregation_genre', 'Genre (H/F)'],
                                    ['desagregation_geographique', 'Géographique'],
                                    ['desagregation_vulnerabilite', 'Vulnérabilité'],
                                    ['desagregation_age', 'Âge'],
                                ] as const
                            ).map(([key, label]) => (
                                <label
                                    key={key}
                                    className="flex cursor-pointer items-center gap-2 rounded-md border p-3 hover:bg-accent/40"
                                >
                                    <input
                                        type="checkbox"
                                        checked={data[key]}
                                        onChange={(e) => setData(key, e.target.checked)}
                                        className="h-4 w-4 rounded border-input"
                                    />
                                    <span className="text-sm">{label}</span>
                                </label>
                            ))}
                        </CardContent>
                    </Card>

                    {/* === Section 6 : Théorie du changement === */}
                    <Card>
                        <CardHeader>
                            <CardTitle>6. Théorie du changement (optionnel)</CardTitle>
                            <CardDescription>Hypothèses et risques liés à l'indicateur.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="hypotheses">Hypothèses</Label>
                                <Textarea
                                    id="hypotheses"
                                    rows={2}
                                    value={data.hypotheses}
                                    onChange={(e) => setData('hypotheses', e.target.value)}
                                    placeholder="Conditions à respecter pour que l'indicateur soit pertinent…"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="risques_associes">Risques associés</Label>
                                <Textarea
                                    id="risques_associes"
                                    rows={2}
                                    value={data.risques_associes}
                                    onChange={(e) => setData('risques_associes', e.target.value)}
                                    placeholder="Risques pouvant compromettre l'atteinte de la cible…"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-2 rounded-md border bg-muted/30 p-4">
                        <Button variant="outline" type="button" asChild>
                            <Link href="/indicateurs">Annuler</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            Créer l'indicateur
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
