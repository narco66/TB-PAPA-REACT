import { Link, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    BarChart3,
    Calendar,
    FileText,
    LoaderCircle,
    Target,
    TrendingDown,
    TrendingUp,
    Users,
} from 'lucide-react';
import { type FormEvent } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatDate, formatPercent } from '@/lib/utils';

interface Palier {
    trimestre: string;
    cible: number | null;
    valeur: number | null;
    ecart: number | null;
    date: string | null;
}

interface Props {
    indicateur: any;
    paliers: Palier[];
    can: { saisie: boolean; update: boolean; validate: boolean };
}

const CATEGORIE_BADGE: Record<string, string> = {
    impact: 'bg-purple-100 text-purple-700 border-purple-300',
    effet: 'bg-blue-100 text-blue-700 border-blue-300',
    produit: 'bg-emerald-100 text-emerald-700 border-emerald-300',
    processus: 'bg-amber-100 text-amber-700 border-amber-300',
};

export default function IndicateurShow({ indicateur, paliers, can }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        date_observation: new Date().toISOString().slice(0, 10),
        periode_libelle: '',
        trimestre: '',
        annee: new Date().getFullYear(),
        valeur: '',
        valeur_hommes: '',
        valeur_femmes: '',
        commentaire: '',
        source_verification: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/indicateurs/${indicateur.id}/valeurs`, { onSuccess: () => reset() });
    };

    const valeurs = indicateur.valeurs ?? [];
    const sp = indicateur.sous_produit;
    const produit = sp?.produit;
    const axe = produit?.axe;
    const papa = axe?.papa;
    const tauxReal = Number(indicateur.taux_realisation ?? 0);
    const dimensions: string[] = indicateur.dimensions_desagregation ?? [];
    const horsPlage: boolean = Boolean(indicateur.hors_plage);

    return (
        <AppLayout
            pageTitle={`Indicateur ${indicateur.code}`}
            breadcrumbs={[{ label: 'Indicateurs', href: '/indicateurs' }, { label: indicateur.code }]}
            actions={
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/indicateurs">
                        <ArrowLeft className="h-4 w-4" />
                        Retour
                    </Link>
                </Button>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Cadre de Mesure du Rendement"
                    title={`${indicateur.code} — ${indicateur.libelle}`}
                    description={indicateur.definition ?? 'Indicateur RBM/GAR avec suivi des paliers et de la désagrégation.'}
                    metrics={[
                        { icon: Target, label: 'Cible', value: indicateur.cible ?? '—' },
                        { icon: BarChart3, label: 'Actuel', value: indicateur.valeur_actuelle ?? '—' },
                        { icon: TrendingUp, label: 'Réalisation', value: `${tauxReal.toFixed(1)}%` },
                        { icon: Calendar, label: 'Fréquence', value: indicateur.frequence_collecte },
                    ]}
                />

                {/* === Bandeau catégorisation + alerte plage === */}
                <Card>
                    <CardContent className="flex flex-wrap items-center justify-between gap-3 p-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="outline" className={CATEGORIE_BADGE[indicateur.categorie] ?? ''}>
                                {indicateur.categorie_libelle ?? indicateur.categorie}
                            </Badge>
                            <Badge variant="outline">{indicateur.type}</Badge>
                            <Badge variant="outline">{indicateur.polarite_libelle ?? indicateur.polarite}</Badge>
                            {indicateur.unite && <Badge variant="secondary">Unité : {indicateur.unite}</Badge>}
                            {dimensions.length > 0 && (
                                <Badge variant="outline" className="border-blue-300 text-blue-700">
                                    <Users className="mr-1 h-3 w-3" />
                                    Désagrégé : {dimensions.join(' · ')}
                                </Badge>
                            )}
                        </div>
                        {horsPlage && (
                            <Badge variant="destructive">
                                <AlertTriangle className="mr-1 h-3 w-3" />
                                Hors plage acceptable
                            </Badge>
                        )}
                    </CardContent>
                </Card>

                {/* === Performance + Rattachement === */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Target className="h-4 w-4" />
                                Performance globale
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-3 gap-3 text-center">
                                <div className="rounded-md border bg-muted/30 p-3">
                                    <p className="text-xs text-muted-foreground">Baseline</p>
                                    <p className="text-xl font-bold tabular-nums">{indicateur.baseline ?? '—'}</p>
                                </div>
                                <div className="rounded-md border bg-primary/5 p-3">
                                    <p className="text-xs text-muted-foreground">Valeur actuelle</p>
                                    <p className="text-xl font-bold tabular-nums text-primary">{indicateur.valeur_actuelle ?? '—'}</p>
                                </div>
                                <div className="rounded-md border bg-emerald-50 p-3">
                                    <p className="text-xs text-muted-foreground">Cible</p>
                                    <p className="text-xl font-bold tabular-nums">{indicateur.cible ?? '—'}</p>
                                </div>
                            </div>
                            <div className="space-y-1.5">
                                <div className="flex justify-between text-sm">
                                    <span>Taux de réalisation</span>
                                    <span className="tabular-nums font-medium">{formatPercent(tauxReal)}</span>
                                </div>
                                <Progress
                                    value={Math.min(100, Math.max(0, tauxReal))}
                                    indicatorClassName={
                                        tauxReal >= 75 ? 'bg-emerald-500'
                                            : tauxReal >= 40 ? 'bg-amber-500'
                                                : 'bg-rose-500'
                                    }
                                />
                            </div>
                            {(indicateur.seuil_alerte_bas !== null || indicateur.seuil_alerte_haut !== null) && (
                                <div className="rounded-md border bg-muted/30 p-3 text-xs">
                                    <p className="mb-1 font-semibold">Plage acceptable</p>
                                    <p className="text-muted-foreground">
                                        Entre {indicateur.seuil_alerte_bas ?? '—'} et {indicateur.seuil_alerte_haut ?? '—'} ({indicateur.unite ?? ''})
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Rattachement RBM</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <Meta label="PAPA" value={papa ? `${papa.annee} — ${papa.libelle}` : '—'} />
                            <Meta label="Axe" value={axe ? `${axe.code} — ${axe.libelle}` : '—'} />
                            <Meta label="Produit" value={produit ? `${produit.code} — ${produit.libelle}` : '—'} />
                            <Meta label="Sous-Produit" value={sp ? `${sp.code} — ${sp.libelle}` : '—'} />
                        </CardContent>
                    </Card>
                </div>

                {/* === Paliers trimestriels === */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Calendar className="h-4 w-4" />
                            Paliers trimestriels
                        </CardTitle>
                        <CardDescription>Trajectoire intermédiaire vs réalisation observée</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            {paliers.map((p) => (
                                <div key={p.trimestre} className="rounded-md border bg-muted/20 p-3">
                                    <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        {p.trimestre}
                                    </p>
                                    <div className="mt-1 flex items-baseline justify-between">
                                        <span className="text-xs text-muted-foreground">Cible</span>
                                        <span className="text-sm font-medium tabular-nums">{p.cible ?? '—'}</span>
                                    </div>
                                    <div className="flex items-baseline justify-between">
                                        <span className="text-xs text-muted-foreground">Réalisé</span>
                                        <span className="text-sm font-bold tabular-nums">{p.valeur ?? '—'}</span>
                                    </div>
                                    {p.ecart !== null && (
                                        <Badge
                                            variant="outline"
                                            className={`mt-2 ${p.ecart >= 0 ? 'border-emerald-300 text-emerald-700' : 'border-rose-300 text-rose-700'}`}
                                        >
                                            {p.ecart >= 0 ? <TrendingUp className="mr-1 h-3 w-3" /> : <TrendingDown className="mr-1 h-3 w-3" />}
                                            Écart {p.ecart > 0 ? '+' : ''}{p.ecart}
                                        </Badge>
                                    )}
                                    {p.date && <p className="mt-1 text-[10px] text-muted-foreground">{formatDate(p.date)}</p>}
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* === Méthodologie === */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Méthodologie CMR</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <Meta label="Instrument de collecte" value={indicateur.instrument_collecte ?? '—'} />
                        <Meta label="Source de données" value={indicateur.source_donnees ?? '—'} />
                        <Meta
                            label="Responsable de collecte"
                            value={indicateur.responsable_collecte?.name ?? '—'}
                            sub={indicateur.responsable_collecte?.fonction}
                        />
                        <Meta
                            label="Responsable de validation"
                            value={indicateur.responsable?.name ?? '—'}
                            sub={indicateur.responsable?.fonction}
                        />
                        {indicateur.methode_calcul && (
                            <div className="md:col-span-2">
                                <p className="text-xs font-medium text-muted-foreground">Méthode de calcul</p>
                                <p className="mt-1 rounded-md bg-muted/40 p-3 text-sm leading-relaxed">
                                    {indicateur.methode_calcul}
                                </p>
                            </div>
                        )}
                        {indicateur.hypotheses && (
                            <div className="md:col-span-2">
                                <p className="text-xs font-medium text-muted-foreground">Hypothèses</p>
                                <p className="mt-1 rounded-md bg-muted/40 p-3 text-sm leading-relaxed">{indicateur.hypotheses}</p>
                            </div>
                        )}
                        {indicateur.risques_associes && (
                            <div className="md:col-span-2">
                                <p className="text-xs font-medium text-muted-foreground">Risques associés</p>
                                <p className="mt-1 rounded-md border-amber-200 bg-amber-50/60 p-3 text-sm leading-relaxed">
                                    {indicateur.risques_associes}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* === Saisie périodique === */}
                {can.saisie && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <TrendingUp className="h-4 w-4" />
                                Saisie d'une nouvelle observation
                            </CardTitle>
                            <CardDescription>Horodatée et tracée dans le journal d'audit.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-4">
                                <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="date_observation">Date *</Label>
                                        <Input
                                            id="date_observation"
                                            type="date"
                                            value={data.date_observation}
                                            onChange={(e) => setData('date_observation', e.target.value)}
                                            required
                                        />
                                        {errors.date_observation && <p className="text-xs text-destructive">{errors.date_observation}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Trimestre</Label>
                                        <Select value={data.trimestre} onValueChange={(v) => setData('trimestre', v)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="—" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {['T1', 'T2', 'T3', 'T4'].map((t) => (
                                                    <SelectItem key={t} value={t}>{t}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="annee">Année</Label>
                                        <Input
                                            id="annee"
                                            type="number"
                                            value={data.annee}
                                            onChange={(e) => setData('annee', Number(e.target.value))}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="valeur">Valeur observée *</Label>
                                        <Input
                                            id="valeur"
                                            type="number"
                                            step="0.0001"
                                            value={data.valeur}
                                            onChange={(e) => setData('valeur', e.target.value)}
                                            required
                                        />
                                        {errors.valeur && <p className="text-xs text-destructive">{errors.valeur}</p>}
                                    </div>
                                </div>

                                {indicateur.desagregation_genre && (
                                    <div className="grid grid-cols-2 gap-3 rounded-md border bg-blue-50/40 p-3">
                                        <div className="space-y-2">
                                            <Label htmlFor="valeur_hommes">Dont hommes</Label>
                                            <Input
                                                id="valeur_hommes"
                                                type="number"
                                                step="0.0001"
                                                value={data.valeur_hommes}
                                                onChange={(e) => setData('valeur_hommes', e.target.value)}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="valeur_femmes">Dont femmes</Label>
                                            <Input
                                                id="valeur_femmes"
                                                type="number"
                                                step="0.0001"
                                                value={data.valeur_femmes}
                                                onChange={(e) => setData('valeur_femmes', e.target.value)}
                                            />
                                        </div>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="source_verification">Source de vérification</Label>
                                    <Input
                                        id="source_verification"
                                        value={data.source_verification}
                                        onChange={(e) => setData('source_verification', e.target.value)}
                                        placeholder="Référence document, rapport, PV…"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="commentaire">Commentaire</Label>
                                    <Textarea
                                        id="commentaire"
                                        rows={2}
                                        value={data.commentaire}
                                        onChange={(e) => setData('commentaire', e.target.value)}
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                        Enregistrer la valeur
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {/* === Historique === */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <FileText className="h-4 w-4" />
                            Historique des observations ({valeurs.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Date</TableHead>
                                    <TableHead>Période</TableHead>
                                    <TableHead className="text-right">Valeur</TableHead>
                                    {indicateur.desagregation_genre && <TableHead className="text-right">H/F</TableHead>}
                                    <TableHead>Source</TableHead>
                                    <TableHead>Saisi par</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {valeurs.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={indicateur.desagregation_genre ? 6 : 5} className="py-6 text-center text-sm text-muted-foreground">
                                            Aucune valeur encore saisie.
                                        </TableCell>
                                    </TableRow>
                                ) : valeurs.map((v: any) => (
                                    <TableRow key={v.id}>
                                        <TableCell className="text-sm">{formatDate(v.date_observation)}</TableCell>
                                        <TableCell className="text-xs">
                                            {v.trimestre && v.annee ? `${v.trimestre} ${v.annee}` : v.periode_libelle ?? '—'}
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums font-semibold">
                                            {v.valeur} {indicateur.unite ?? ''}
                                        </TableCell>
                                        {indicateur.desagregation_genre && (
                                            <TableCell className="text-right text-xs tabular-nums">
                                                {v.valeur_hommes !== null && v.valeur_femmes !== null
                                                    ? `${v.valeur_hommes} / ${v.valeur_femmes}`
                                                    : '—'}
                                            </TableCell>
                                        )}
                                        <TableCell className="text-xs">{v.source_verification ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{v.saisi_par?.name ?? '—'}</TableCell>
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

function Meta({ label, value, sub }: { label: string; value: string; sub?: string | null }) {
    return (
        <div>
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-sm font-medium truncate">{value}</p>
            {sub && <p className="text-xs text-muted-foreground truncate">{sub}</p>}
        </div>
    );
}
