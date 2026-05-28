import { Link, router } from '@inertiajs/react';
import { Plus, Receipt } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { ExcelExportButton } from '@/components/common/excel-export-button';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function ExpenseRequestsIndex({ requests, statuts, types, filters, can }: any) {
    const items = requests.data ?? [];

    const setFilter = (key: string, value: string) => {
        router.get('/expense/requests', { ...filters, [key]: value || undefined }, { preserveState: true, replace: true });
    };

    const fmt = (n: number) => new Intl.NumberFormat('fr-FR').format(Number(n || 0));

    return (
        <AppLayout
            pageTitle="Expressions du besoin"
            breadcrumbs={[{ label: 'Chaîne de la dépense', href: '/expense/requests' }, { label: 'Expressions du besoin' }]}
            actions={
                <>
                    <ExcelExportButton journal="expressions" filtres={filters} />
                    <PdfExportButton reportKey="liste_expense_requests" filtres={filters} />
                    {can.create && (
                        <Button asChild>
                            <Link href="/expense/requests/create">
                                <Plus className="h-4 w-4" />
                                Nouvelle expression
                            </Link>
                        </Button>
                    )}
                </>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Chaîne de la dépense — Étape 1"
                    title="Expression du besoin"
                    description="Initiation institutionnelle de la dépense. Workflow : brouillon → soumis → validation hiérarchique → engagement budgétaire (cycle IPSAS)."
                    metrics={[
                        { icon: Receipt, label: 'Total', value: String(requests.total ?? items.length) },
                    ]}
                />

                <Card>
                    <CardContent className="p-4 space-y-4">
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <input
                                type="search"
                                placeholder="Rechercher numéro ou objet…"
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
                            <select
                                className="rounded-md border px-3 py-2 text-sm"
                                value={filters?.type ?? ''}
                                onChange={(e) => setFilter('type', e.target.value)}
                            >
                                <option value="">Tous types</option>
                                {Object.entries(types).map(([k, v]) => <option key={k} value={k}>{v as string}</option>)}
                            </select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>N°</TableHead>
                                    <TableHead>Objet</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Demandeur</TableHead>
                                    <TableHead>Département</TableHead>
                                    <TableHead className="text-right">Montant</TableHead>
                                    <TableHead>Statut</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.length === 0 ? (
                                    <TableRow><TableCell colSpan={7} className="py-8 text-center text-muted-foreground">Aucune expression du besoin.</TableCell></TableRow>
                                ) : items.map((r: any) => (
                                    <TableRow key={r.id}>
                                        <TableCell className="font-mono text-xs">{r.numero}</TableCell>
                                        <TableCell><Link href={`/expense/requests/${r.id}`} className="font-medium hover:underline">{r.objet}</Link></TableCell>
                                        <TableCell className="text-xs">{types[r.type_engagement] ?? r.type_engagement}</TableCell>
                                        <TableCell className="text-xs">{r.demandeur?.name ?? '—'}</TableCell>
                                        <TableCell className="text-xs">{r.departement?.code ?? '—'}</TableCell>
                                        <TableCell className="text-right tabular-nums text-sm">{fmt(r.montant_estime)} {r.devise}</TableCell>
                                        <TableCell><span className="text-xs">{r.statut}</span></TableCell>
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
