import { Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Download,
    FileSpreadsheet,
    LayersIcon,
    LoaderCircle,
    Microscope,
    Upload,
    XCircle,
} from 'lucide-react';
import { type FormEvent, useMemo, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';

interface AnalyseFeuille {
    nom: string;
    index: number;
    type_detecte: string | null;
    nb_lignes: number;
    nb_colonnes: number;
    en_tetes: string[];
    mapping_suggere: Record<string, string | null>;
    colonnes_inconnues: string[];
    champs_manquants: string[];
}

interface Analyse {
    fichier: string;
    nb_feuilles: number;
    feuilles: AnalyseFeuille[];
}

const TYPE_BADGES: Record<string, string> = {
    budget: 'bg-emerald-100 text-emerald-700 border-emerald-300',
    axes: 'bg-blue-100 text-blue-700 border-blue-300',
    produits: 'bg-indigo-100 text-indigo-700 border-indigo-300',
    sous_produits: 'bg-violet-100 text-violet-700 border-violet-300',
    activites: 'bg-amber-100 text-amber-700 border-amber-300',
    taches: 'bg-rose-100 text-rose-700 border-rose-300',
    indicateurs: 'bg-cyan-100 text-cyan-700 border-cyan-300',
};

interface SavedMapping {
    id: number;
    user_id: number | null;
    type_donnees: string;
    libelle: string;
    mapping: Record<string, string | null>;
    partage: boolean;
    created_at: string;
}

export default function ImportsIndex({ exercices, historique, mappings = [] }: { exercices: any[]; historique: any[]; mappings?: SavedMapping[] }) {
    const { flash } = usePage<any>().props;
    const importResult = flash?.import_result;
    const { data, setData, post, processing, errors, progress } = useForm<{
        exercice_id: number | '';
        fichier: File | null;
        dry_run: boolean;
        feuille: string;
    }>({
        exercice_id: exercices[0]?.id ?? '',
        fichier: null,
        dry_run: true,
        feuille: '',
    });

    const [analyse, setAnalyse] = useState<Analyse | null>(null);
    const [analyseEnCours, setAnalyseEnCours] = useState(false);
    const [erreurAnalyse, setErreurAnalyse] = useState<string | null>(null);
    const [feuillesSelectionnees, setFeuillesSelectionnees] = useState<Record<string, boolean>>({});
    const [importMultiEnCours, setImportMultiEnCours] = useState(false);
    const [savingMapping, setSavingMapping] = useState<string | null>(null); // nom feuille en cours de save

    const sauverMapping = async (feuille: AnalyseFeuille) => {
        if (!feuille.type_detecte) return;
        const libelle = window.prompt(`Nom du mapping pour ${feuille.type_detecte} :`, `Mapping ${feuille.nom}`);
        if (!libelle) return;
        const partage = window.confirm('Rendre ce mapping accessible à toute l\'équipe ? (OK = oui, Annuler = privé)');

        setSavingMapping(feuille.nom);
        try {
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch('/budget/imports/mappings', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    type_donnees: feuille.type_detecte,
                    libelle,
                    mapping: feuille.mapping_suggere,
                    partage,
                }),
            });
            const json = await res.json();
            if (json.succes) {
                router.reload({ only: ['mappings'] });
            } else {
                setErreurAnalyse('Erreur lors de la sauvegarde du mapping.');
            }
        } catch (e: any) {
            setErreurAnalyse(e?.message ?? 'Erreur réseau.');
        } finally {
            setSavingMapping(null);
        }
    };

    const supprimerMapping = async (id: number) => {
        if (!window.confirm('Supprimer ce mapping ?')) return;
        const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        await fetch(`/budget/imports/mappings/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        router.reload({ only: ['mappings'] });
    };

    const feuillesImportables = useMemo(
        () => analyse?.feuilles.filter((f) => f.type_detecte && f.type_detecte !== 'budget') ?? [],
        [analyse],
    );

    const lancerImportMulti = () => {
        if (!data.fichier || !analyse) return;
        const feuillesAEnvoyer = feuillesImportables
            .filter((f) => feuillesSelectionnees[f.nom])
            .map((f) => ({ nom: f.nom, type: f.type_detecte! }));

        if (feuillesAEnvoyer.length === 0) {
            setErreurAnalyse('Sélectionnez au moins une feuille à importer.');

            return;
        }

        setImportMultiEnCours(true);
        const formData = new FormData();
        formData.append('exercice_id', String(data.exercice_id));
        formData.append('fichier', data.fichier);
        formData.append('dry_run', data.dry_run ? '1' : '0');
        feuillesAEnvoyer.forEach((f, i) => {
            formData.append(`feuilles[${i}][nom]`, f.nom);
            formData.append(`feuilles[${i}][type]`, f.type);
        });

        router.post('/budget/imports/multi-feuilles', formData, {
            forceFormData: true,
            onFinish: () => setImportMultiEnCours(false),
        });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/budget/imports', { forceFormData: true });
    };

    const analyser = async () => {
        if (!data.fichier) {
            setErreurAnalyse('Sélectionnez un fichier avant l\'analyse.');

            return;
        }
        setAnalyseEnCours(true);
        setErreurAnalyse(null);
        try {
            const formData = new FormData();
            formData.append('fichier', data.fichier);
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const res = await fetch('/budget/imports/analyser', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: formData,
                credentials: 'same-origin',
            });
            const json = await res.json();
            if (json.succes) {
                setAnalyse(json.analyse);
                const feuilleBudget = json.analyse.feuilles.find((f: AnalyseFeuille) => f.type_detecte === 'budget');
                setData('feuille', feuilleBudget?.nom ?? '');
                // Pré-cocher toutes les feuilles importables (non-budget)
                const presel: Record<string, boolean> = {};
                for (const f of json.analyse.feuilles) {
                    if (f.type_detecte && f.type_detecte !== 'budget') {
                        presel[f.nom] = true;
                    }
                }
                setFeuillesSelectionnees(presel);
            } else {
                setErreurAnalyse(json.message ?? 'Échec de l\'analyse.');
            }
        } catch (e: any) {
            setErreurAnalyse(e?.message ?? 'Erreur réseau lors de l\'analyse.');
        } finally {
            setAnalyseEnCours(false);
        }
    };

    return (
        <AppLayout
            pageTitle="Import budgétaire"
            breadcrumbs={[{ label: 'Budget', href: '/budget' }, { label: 'Imports' }]}
            actions={
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/budget">
                        <ArrowLeft className="h-4 w-4" />
                        Retour
                    </Link>
                </Button>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Pilotage budgétaire"
                    title="Import Excel multi-feuilles"
                    description="Procédure progressive : analyse automatique des feuilles, détection des types, mapping suggéré, prévisualisation, import contrôlé."
                    metrics={[
                        { icon: FileSpreadsheet, label: 'Exercices', value: Number(exercices.length ?? 0).toLocaleString('fr-FR') },
                        { icon: Upload, label: 'Imports récents', value: Number(historique.length ?? 0).toLocaleString('fr-FR') },
                        { icon: CheckCircle2, label: 'Réussis', value: Number(historique.filter((h: any) => h.statut === 'reussi').length).toLocaleString('fr-FR') },
                        { icon: XCircle, label: 'Erreurs', value: Number(historique.reduce((sum: number, h: any) => sum + Number(h.nb_erreurs ?? 0), 0)).toLocaleString('fr-FR') },
                    ]}
                    footer="Chaîne RBM/GAR : Axe → Produit → Sous-Produit → Activité → Tâche"
                    footerIcon={FileSpreadsheet}
                />

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <CardTitle className="flex items-center gap-2">
                                        <Upload className="h-5 w-5" />
                                        1. Téléverser un fichier Excel
                                    </CardTitle>
                                    <CardDescription>
                                        Fichier mono ou multi-feuilles (.xlsx, .xls, .csv). Cliquez ensuite sur "Analyser" pour détecter automatiquement les feuilles.
                                    </CardDescription>
                                </div>
                                <Button variant="outline" asChild>
                                    <a href="/budget/imports/modele">
                                        <Download className="h-4 w-4" />
                                        Modèle officiel
                                    </a>
                                </Button>
                            </div>
                        </CardHeader>
                        <form onSubmit={submit}>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Exercice cible *</Label>
                                    <Select
                                        value={String(data.exercice_id)}
                                        onValueChange={(v) => setData('exercice_id', Number(v))}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {exercices.map((e: any) => (
                                                <SelectItem key={e.id} value={String(e.id)}>
                                                    {e.annee} - {e.libelle}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="fichier">Fichier Excel ou CSV *</Label>
                                    <div className="rounded-md border-2 border-dashed bg-muted/20 p-6 text-center">
                                        <FileSpreadsheet className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                                        <Input
                                            id="fichier"
                                            type="file"
                                            accept=".xlsx,.xls,.csv"
                                            onChange={(e) => {
                                                setData('fichier', e.target.files?.[0] ?? null);
                                                setAnalyse(null);
                                                setErreurAnalyse(null);
                                            }}
                                            required
                                            className="cursor-pointer"
                                        />
                                        {data.fichier && (
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                {data.fichier.name} ({(data.fichier.size / 1024).toFixed(1)} ko)
                                            </p>
                                        )}
                                    </div>
                                    {errors.fichier && <p className="text-xs text-destructive">{errors.fichier}</p>}
                                    {progress ? <p className="text-xs text-muted-foreground">Upload : {progress.percentage}%</p> : null}
                                </div>

                                <div className="flex items-center justify-between gap-3 rounded-md border bg-blue-50/40 p-3">
                                    <div className="flex items-start gap-2">
                                        <Microscope className="h-5 w-5 mt-0.5 text-blue-600 shrink-0" />
                                        <div>
                                            <p className="text-sm font-medium">2. Analyser le fichier (sans importer)</p>
                                            <p className="text-xs text-muted-foreground">
                                                Détecte les feuilles, types et colonnes — aucune écriture en base.
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={analyser}
                                        disabled={analyseEnCours || !data.fichier}
                                    >
                                        {analyseEnCours ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Microscope className="h-4 w-4" />}
                                        Analyser
                                    </Button>
                                </div>

                                {erreurAnalyse && (
                                    <p className="rounded-md border border-destructive/30 bg-destructive/5 p-2 text-xs text-destructive">
                                        {erreurAnalyse}
                                    </p>
                                )}

                                <label className="flex items-center gap-2 rounded-md border bg-muted/20 px-3 py-2 text-sm cursor-pointer hover:bg-accent">
                                    <input
                                        type="checkbox"
                                        checked={data.dry_run}
                                        onChange={(e) => setData('dry_run', e.target.checked)}
                                        className="h-4 w-4"
                                    />
                                    Prévisualiser seulement, sans intégrer les lignes (mode dry-run)
                                </label>

                                {data.feuille && (
                                    <input type="hidden" name="feuille" value={data.feuille} />
                                )}

                                <div className="rounded-md border bg-muted/20 p-3 text-xs text-muted-foreground">
                                    {data.feuille ? (
                                        <>Feuille budget sélectionnée : <strong>{data.feuille}</strong>. Structure Malabo acceptée : Titre, Chap., Art., Parag., Code Action, Intitulés, Budget 2025, Recettes réalisées, Taux de réalisation, Prévisions 2026, Variation.</>
                                    ) : (
                                        <>Colonnes budget attendues : Code Axe, Code Produit, Code Sous-Produit, Code Activité, Code Tâche, Département, Nature dépense, Ligne budgétaire, Montant prévu, Devise, Année, Source financement.</>
                                    )}
                                </div>
                            </CardContent>
                            <div className="flex items-center justify-end gap-2 border-t bg-muted/30 px-6 py-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
                                    {data.dry_run ? '3. Prévisualiser l\'import' : '3. Lancer l\'import'}
                                </Button>
                            </div>
                        </form>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Historique récent</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fichier</TableHead>
                                        <TableHead>Statut</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {historique.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={2} className="py-4 text-center text-xs text-muted-foreground">
                                                Aucun import effectué.
                                            </TableCell>
                                        </TableRow>
                                    ) : historique.map((h: any) => (
                                        <TableRow key={h.id}>
                                            <TableCell className="text-xs">
                                                <p className="font-medium truncate">{h.fichier_nom}</p>
                                                <p className="text-[10px] text-muted-foreground">
                                                    {h.exercice?.annee} · {formatDate(h.created_at)} · {h.execute_par?.name ?? 'Système'}
                                                </p>
                                                <p className="text-[10px] text-muted-foreground">
                                                    {h.nb_lignes_lues} lues · {h.nb_lignes_creees} créées · {h.nb_erreurs} erreurs
                                                </p>
                                                <div className="flex flex-wrap gap-2 mt-0.5">
                                                    {h.nb_erreurs > 0 && (
                                                        <>
                                                            <a className="text-[10px] text-primary hover:underline" href={`/budget/imports/${h.id}/rapport-erreurs`}>
                                                                CSV
                                                            </a>
                                                            <a className="text-[10px] text-primary hover:underline" href={`/budget/imports/${h.id}/rapport-erreurs.xlsx`}>
                                                                XLSX coloré
                                                            </a>
                                                        </>
                                                    )}
                                                    {h.statut === 'reussi' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                if (window.confirm(`Annuler l'import #${h.id} ? Les ressources créées seront supprimées (soft-delete).`)) {
                                                                    router.post(`/budget/imports/${h.id}/rollback`, {}, { preserveScroll: true });
                                                                }
                                                            }}
                                                            className="text-[10px] text-rose-700 hover:underline"
                                                        >
                                                            ↶ Annuler
                                                        </button>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={h.statut === 'reussi' ? 'success' : h.statut === 'echec' ? 'destructive' : 'secondary'}>
                                                    {h.statut}
                                                </Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {/* ===== Résultat d'analyse multi-feuilles ===== */}
                {analyse && (
                    <AnalysePanel
                        analyse={analyse}
                        feuillesImportables={feuillesImportables}
                        feuillesSelectionnees={feuillesSelectionnees}
                        onToggleFeuille={(nom) => setFeuillesSelectionnees((s) => ({ ...s, [nom]: !s[nom] }))}
                        onImportMulti={lancerImportMulti}
                        importEnCours={importMultiEnCours}
                        dryRun={data.dry_run}
                        onSauverMapping={sauverMapping}
                        savingMapping={savingMapping}
                    />
                )}

                {/* ===== Mappings enregistrés ===== */}
                {mappings.length > 0 && <MappingsPanel mappings={mappings} onSupprimer={supprimerMapping} />}

                {/* ===== Résultat d'import (dry-run ou exécution réelle) ===== */}
                {importResult && <ImportPreview result={importResult} />}
            </div>
        </AppLayout>
    );
}

interface AnalysePanelProps {
    analyse: Analyse;
    feuillesImportables: AnalyseFeuille[];
    feuillesSelectionnees: Record<string, boolean>;
    onToggleFeuille: (nom: string) => void;
    onImportMulti: () => void;
    importEnCours: boolean;
    dryRun: boolean;
    onSauverMapping: (feuille: AnalyseFeuille) => void;
    savingMapping: string | null;
}

function AnalysePanel({
    analyse,
    feuillesImportables,
    feuillesSelectionnees,
    onToggleFeuille,
    onImportMulti,
    importEnCours,
    dryRun,
    onSauverMapping,
    savingMapping,
}: AnalysePanelProps) {
    const nbSelectionnees = feuillesImportables.filter((f) => feuillesSelectionnees[f.nom]).length;

    return (
        <Card className="border-blue-200/60">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Microscope className="h-5 w-5 text-blue-600" />
                    Analyse du fichier : {analyse.fichier}
                </CardTitle>
                <CardDescription>
                    {analyse.nb_feuilles} feuille(s) détectée(s) · {feuillesImportables.length} importable(s) en multi-feuilles
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {feuillesImportables.length > 0 && (
                    <div className="rounded-md border-2 border-blue-300 bg-blue-50/40 p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="font-semibold flex items-center gap-2">
                                    <LayersIcon className="h-4 w-4" />
                                    Importer en cascade RBM
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Cochez les feuilles à importer. L'ordre d'exécution sera : Axes → Produits → Sous-Produits → Activités → Tâches → Indicateurs.
                                </p>
                            </div>
                            <Button
                                onClick={onImportMulti}
                                disabled={importEnCours || nbSelectionnees === 0}
                                variant={dryRun ? 'outline' : 'default'}
                            >
                                {importEnCours ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
                                {dryRun ? `Prévisualiser (${nbSelectionnees})` : `Importer (${nbSelectionnees})`}
                            </Button>
                        </div>
                        <div className="mt-3 space-y-2">
                            {feuillesImportables.map((f) => (
                                <div
                                    key={f.nom}
                                    className="flex items-center gap-2 rounded-md border bg-card p-2 text-sm hover:bg-accent/30"
                                >
                                    <label className="flex cursor-pointer items-center gap-2 flex-1">
                                        <input
                                            type="checkbox"
                                            checked={feuillesSelectionnees[f.nom] ?? false}
                                            onChange={() => onToggleFeuille(f.nom)}
                                            className="h-4 w-4"
                                        />
                                        <span className="flex-1 truncate">
                                            <span className="font-medium">{f.nom}</span>
                                            <span className="ml-1 text-xs text-muted-foreground">({f.type_detecte} · {f.nb_lignes} l.)</span>
                                        </span>
                                    </label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => onSauverMapping(f)}
                                        disabled={savingMapping === f.nom}
                                        title="Sauvegarder ce mapping pour réutilisation"
                                    >
                                        {savingMapping === f.nom ? (
                                            <LoaderCircle className="h-3.5 w-3.5 animate-spin" />
                                        ) : (
                                            <span className="text-xs">💾 Sauver mapping</span>
                                        )}
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
                {analyse.feuilles.map((f) => (
                    <div key={f.index} className="rounded-md border bg-card p-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div className="flex items-center gap-2">
                                <span className="font-mono text-xs text-muted-foreground">#{f.index + 1}</span>
                                <span className="font-semibold">{f.nom}</span>
                                {f.type_detecte && (
                                    <Badge variant="outline" className={TYPE_BADGES[f.type_detecte] ?? ''}>
                                        {f.type_detecte}
                                    </Badge>
                                )}
                                {!f.type_detecte && <Badge variant="outline">type non détecté</Badge>}
                            </div>
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <span>{f.nb_lignes} ligne(s)</span>
                                <span>·</span>
                                <span>{f.nb_colonnes} colonne(s)</span>
                            </div>
                        </div>

                        {f.en_tetes.length > 0 && (
                            <div className="mt-3 grid gap-3 md:grid-cols-2">
                                <div>
                                    <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        En-têtes détectés
                                    </p>
                                    <div className="space-y-1">
                                        {f.en_tetes.map((h) => {
                                            const match = f.mapping_suggere[h];

                                            return (
                                                <div key={h} className="flex items-center justify-between gap-2 rounded-md bg-muted/30 px-2 py-1 text-xs">
                                                    <span className="font-mono truncate">{h}</span>
                                                    {match ? (
                                                        <Badge variant="outline" className="border-emerald-300 bg-emerald-50 text-emerald-700">
                                                            → {match}
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="border-amber-300 bg-amber-50 text-amber-700">
                                                            non mappé
                                                        </Badge>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>

                                <div>
                                    {f.champs_manquants.length > 0 && (
                                        <div className="mb-3">
                                            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                Champs canoniques manquants
                                            </p>
                                            <div className="flex flex-wrap gap-1">
                                                {f.champs_manquants.map((c) => (
                                                    <Badge key={c} variant="outline" className="border-rose-300 bg-rose-50 text-rose-700">
                                                        {c}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    )}

                                    {f.colonnes_inconnues.length > 0 && (
                                        <div>
                                            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                Colonnes non mappées
                                            </p>
                                            <div className="flex flex-wrap gap-1">
                                                {f.colonnes_inconnues.map((c) => (
                                                    <Badge key={c} variant="outline" className="border-amber-300 text-amber-700">
                                                        {c}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                ))}

                <p className="rounded-md border bg-blue-50/40 p-3 text-xs text-blue-700">
                    💡 L'import multi-feuilles (Axes, Produits, Activités, Tâches, Indicateurs) sera disponible
                    progressivement. Pour cette version, seules les feuilles de type <strong>budget</strong> sont importées
                    via le bouton "Lancer l'import" ci-dessus.
                </p>
            </CardContent>
        </Card>
    );
}

function ImportPreview({ result }: { result: any }) {
    const stats = result.stats ?? {};
    const erreurs = result.erreurs ?? [];

    return (
        <Card className={result.succes ? 'border-emerald-200' : 'border-destructive/30'}>
            <CardHeader>
                <CardTitle className="text-base">Prévisualisation et contrôle</CardTitle>
                <CardDescription>
                    {stats.lues ?? 0} lignes détectées · {stats.valides ?? 0} valides · {stats.erreurs ?? 0} en erreur · {stats.doublons ?? 0} doublons · total {Number(stats.montant_total ?? 0).toLocaleString('fr-FR')}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Repartition title="Répartition par Axe" data={result.repartition_axes ?? {}} />
                    <Repartition title="Répartition par département" data={result.repartition_departements ?? {}} />
                </div>
                {erreurs.length > 0 && (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Ligne</TableHead>
                                    <TableHead>Champ</TableHead>
                                    <TableHead>Erreur</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {erreurs.slice(0, 50).map((e: any, idx: number) => (
                                    <TableRow key={idx}>
                                        <TableCell className="w-20 tabular-nums">{e.ligne ?? '-'}</TableCell>
                                        <TableCell className="font-medium">{e.champ ?? '-'}</TableCell>
                                        <TableCell>{e.message ?? String(e)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function MappingsPanel({ mappings, onSupprimer }: { mappings: SavedMapping[]; onSupprimer: (id: number) => void }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Mappings enregistrés ({mappings.length})</CardTitle>
                <CardDescription>Réutilisables pour standardiser l'import de fichiers de structures variées.</CardDescription>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Libellé</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Colonnes mappées</TableHead>
                            <TableHead>Partage</TableHead>
                            <TableHead className="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {mappings.map((m) => {
                            const nbCols = Object.values(m.mapping ?? {}).filter(Boolean).length;

                            return (
                                <TableRow key={m.id}>
                                    <TableCell className="font-medium">{m.libelle}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" className={TYPE_BADGES[m.type_donnees] ?? ''}>
                                            {m.type_donnees}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">{nbCols} colonne(s)</TableCell>
                                    <TableCell>
                                        {m.partage ? (
                                            <Badge variant="outline" className="border-emerald-300 text-emerald-700">Partagé</Badge>
                                        ) : (
                                            <Badge variant="secondary">Privé</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="sm" onClick={() => onSupprimer(m.id)}>
                                            Supprimer
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}

function Repartition({ title, data }: { title: string; data: Record<string, number> }) {
    const entries = Object.entries(data);

    return (
        <div className="rounded-md border p-3">
            <p className="mb-2 text-sm font-medium">{title}</p>
            {entries.length === 0 ? (
                <p className="text-xs text-muted-foreground">Aucune donnée consolidée.</p>
            ) : (
                <div className="space-y-1">
                    {entries.map(([key, value]) => (
                        <div key={key} className="flex items-center justify-between gap-3 text-sm">
                            <span className="truncate">{key}</span>
                            <span className="tabular-nums">{Number(value).toLocaleString('fr-FR')}</span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
