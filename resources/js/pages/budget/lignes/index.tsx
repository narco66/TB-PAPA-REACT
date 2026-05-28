import { Link, router } from '@inertiajs/react';
import { FileText, ListTree, PiggyBank, Plus, Search, Wallet } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { RbmStatusBadge } from '@/components/rbm/rbm-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/lib/utils';

const TYPE_LIBELLES: Record<string, string> = {
    recette_interne: 'Recette interne',
    recette_externe: 'Recette externe',
    fonctionnement: 'Fonctionnement',
    investissement: 'Investissement',
    equipement: 'Équipement',
    dotation: 'Dotation',
    dette: 'Dette',
    transfert: 'Transfert',
    autre: 'Autre',
};

export default function LignesIndex({ lignes, exercices, axes, sources, filters, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [exerciceId, setExerciceId] = useState(String(filters.exercice_id ?? 'all'));
    const [nature, setNature] = useState(filters.nature ?? 'all');
    const [type, setType] = useState(filters.type_budget ?? 'all');
    const lignesData = lignes.data ?? [];
    const totalVisible = lignesData.reduce((sum: number, ligne: any) => sum + Number(ligne.montant_total ?? 0), 0);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/budget/lignes', {
            q,
            exercice_id: exerciceId === 'all' ? '' : exerciceId,
            nature: nature === 'all' ? '' : nature,
            type_budget: type === 'all' ? '' : type,
        }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Lignes budgétaires"
            breadcrumbs={[{ label: 'Budget', href: '/budget' }, { label: 'Lignes' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={lignesData} filename="lignes_budgetaires" meta={{ total: lignes.total ?? lignesData.length, filtres: filters }} />
                    <PdfExportButton reportKey="budget_lignes" filtres={filters} />
                    <PdfQuickButton
                        reportKey="budget_lignes"
                        params={{
                            q,
                            exercice_id: exerciceId === 'all' ? undefined : exerciceId,
                            nature: nature === 'all' ? undefined : nature,
                            type_budget: type === 'all' ? undefined : type,
                            axe_id: filters.axe_id ?? undefined,
                            source_financement_id: filters.source_financement_id ?? undefined,
                            pilier: filters.pilier ?? undefined,
                        }}
                    >
                        Exporter PDF
                    </PdfQuickButton>
                    {can.create && (
                        <Button asChild><Link href="/budget/lignes/create"><Plus className="h-4 w-4" />Nouvelle ligne</Link></Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="Pilotage budgétaire"
                title="Lignes budgétaires"
                description="Suivi des recettes, dépenses, sources de financement et rattachements RBM des lignes budgétaires."
                metrics={[
                    { icon: ListTree, label: 'Lignes', value: Number(lignes.total ?? lignesData.length).toLocaleString('fr-FR') },
                    { icon: Wallet, label: 'Exercices', value: Number(exercices.length ?? 0).toLocaleString('fr-FR') },
                    { icon: PiggyBank, label: 'Montant visible', value: totalVisible.toLocaleString('fr-FR') },
                    { icon: FileText, label: 'Sources', value: Number(sources.length ?? 0).toLocaleString('fr-FR') },
                ]}
                footer="Rattachement budgétaire aux axes RBM, sources et piliers"
                footerIcon={PiggyBank}
            />

            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-6">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Libellé, code…" className="pl-9" />
                        </div>
                        <Select value={exerciceId} onValueChange={setExerciceId}>
                            <SelectTrigger><SelectValue placeholder="Exercice" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous</SelectItem>
                                {exercices.map((e: any) => (<SelectItem key={e.id} value={String(e.id)}>{e.annee}</SelectItem>))}
                            </SelectContent>
                        </Select>
                        <Select value={nature} onValueChange={setNature}>
                            <SelectTrigger><SelectValue placeholder="Nature" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Recettes + Dépenses</SelectItem>
                                <SelectItem value="recette">Recettes</SelectItem>
                                <SelectItem value="depense">Dépenses</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select value={type} onValueChange={setType}>
                            <SelectTrigger><SelectValue placeholder="Type" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous types</SelectItem>
                                {Object.entries(TYPE_LIBELLES).map(([k, v]) => (<SelectItem key={k} value={k}>{v}</SelectItem>))}
                            </SelectContent>
                        </Select>
                        <Button type="submit" variant="outline">Filtrer</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Code</TableHead>
                                <TableHead>Libellé / Catégorie</TableHead>
                                <TableHead>Axe RBM</TableHead>
                                <TableHead className="text-right">CEEAC-EM</TableHead>
                                <TableHead className="text-right">PTF</TableHead>
                                <TableHead className="text-right">TOTAL</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {lignesData.length === 0 ? (
                                <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground"><ListTree className="mx-auto mb-2 h-8 w-8 opacity-30" />Aucune ligne budgétaire.</TableCell></TableRow>
                            ) : lignesData.map((l: any) => (
                                <TableRow key={l.id} className={l.nature === 'recette' ? 'bg-emerald-50/30' : ''}>
                                    <TableCell className="font-mono text-xs">
                                        {l.code_action ?? l.paragraphe_code ?? l.article_code ?? '—'}
                                        {l.pilier && <Badge variant="secondary" className="ml-1 text-[9px]">P{l.pilier}</Badge>}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={`/budget/lignes/${l.id}`} className="font-medium hover:underline line-clamp-1">{l.libelle}</Link>
                                        <p className="text-xs text-muted-foreground">
                                            <Badge variant="outline" className="mr-1 text-[10px]">{TYPE_LIBELLES[l.type_budget] ?? l.type_budget}</Badge>
                                            {l.source ? l.source.code : '—'}
                                            {l.exercice ? ` · ${l.exercice.annee}` : ''}
                                        </p>
                                    </TableCell>
                                    <TableCell className="text-xs">
                                        {l.axe ? `${l.axe.code} — ${l.axe.libelle}` : '—'}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums text-xs">{formatCurrency(l.montant_ceeac_em)}</TableCell>
                                    <TableCell className="text-right tabular-nums text-xs">{formatCurrency(l.montant_ptf)}</TableCell>
                                    <TableCell className="text-right tabular-nums font-semibold">{formatCurrency(l.montant_total)}</TableCell>
                                    <TableCell><Button size="sm" variant="ghost" asChild><Link href={`/budget/lignes/${l.id}`}>Voir</Link></Button></TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <Pagination pagination={lignes} label="lignes budgétaires" />
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}
