import { Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, Eye, FileText, Search } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { PdfQuickButton } from '@/components/rbm/pdf-quick-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';

function formatTaille(o: number): string {
    if (o < 1024) return `${o} o`;
    if (o < 1048576) return `${(o / 1024).toFixed(1)} ko`;
    return `${(o / 1048576).toFixed(1)} Mo`;
}

export default function ReportsHistorique({ rapports, filtres, categories }: any) {
    const [q, setQ] = useState(filtres.q ?? '');
    const [cat, setCat] = useState(filtres.categorie ?? 'all');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/rapports/historique', { q, categorie: cat === 'all' ? '' : cat }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Historique des rapports"
            breadcrumbs={[{ label: 'Rapports', href: '/rapports' }, { label: 'Historique' }]}
            actions={
                <div className="flex gap-2">
                    <PdfQuickButton
                        reportKey="historique_rapports"
                        params={{
                            q,
                            categorie: cat === 'all' ? undefined : cat,
                        }}
                    >
                        Exporter le registre
                    </PdfQuickButton>
                    <Button variant="outline" asChild>
                        <Link href="/rapports"><ArrowLeft className="h-4 w-4" />Retour aux rapports</Link>
                    </Button>
                </div>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Historique des rapports"
                    description="Consultation des rapports générés, téléchargements et traces associées."
                />
            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-4">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Titre…" className="pl-9" />
                        </div>
                        <Select value={cat} onValueChange={setCat}>
                            <SelectTrigger><SelectValue placeholder="Catégorie" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Toutes catégories</SelectItem>
                                {Object.entries(categories).map(([k, v]) => (
                                    <SelectItem key={k} value={k}>{v as string}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button type="submit" variant="outline">Filtrer</Button>
                    </form>
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Document</TableHead>
                                <TableHead>Catégorie</TableHead>
                                <TableHead>Code</TableHead>
                                <TableHead>Émis par</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead className="text-right">Taille</TableHead>
                                <TableHead className="text-right">Téléch.</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rapports.data.length === 0 ? (
                                <TableRow><TableCell colSpan={8} className="py-8 text-center text-muted-foreground">
                                    <FileText className="mx-auto mb-2 h-8 w-8 opacity-30" />Aucun rapport généré pour l'instant.
                                </TableCell></TableRow>
                            ) : rapports.data.map((r: any) => (
                                <TableRow key={r.id}>
                                    <TableCell>
                                        <p className="font-medium line-clamp-1">{r.titre}</p>
                                        <p className="font-mono text-[10px] text-muted-foreground">{r.nom_fichier}</p>
                                    </TableCell>
                                    <TableCell><Badge variant="secondary">{r.categorie}</Badge></TableCell>
                                    <TableCell className="font-mono text-xs">{r.code_verification}</TableCell>
                                    <TableCell className="text-xs">{r.genereur?.name ?? '—'}</TableCell>
                                    <TableCell className="text-xs">{formatDate(r.genere_at)}</TableCell>
                                    <TableCell className="text-right text-xs tabular-nums">{formatTaille(r.taille_octets)}</TableCell>
                                    <TableCell className="text-right tabular-nums">{r.nb_telechargements}</TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button size="sm" variant="ghost" asChild title="Aperçu">
                                                <a href={`/rapports/${r.id}/preview`} target="_blank" rel="noreferrer">
                                                    <Eye className="h-4 w-4" />
                                                </a>
                                            </Button>
                                            <Button size="sm" variant="ghost" asChild title="Télécharger">
                                                <a href={`/rapports/${r.id}/telecharger`}>
                                                    <Download className="h-4 w-4" />
                                                </a>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {rapports.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <p>{rapports.from}–{rapports.to} sur {rapports.total}</p>
                    <div className="flex gap-1">
                        {rapports.links.map((link: any, i: number) => (
                            <button
                                key={i}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })}
                                className={`rounded px-3 py-1.5 text-sm ${link.active ? 'bg-primary text-primary-foreground' : link.url ? 'border hover:bg-accent' : 'opacity-40'}`}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                </div>
            )}
                </div>
</AppLayout>
    );
}
