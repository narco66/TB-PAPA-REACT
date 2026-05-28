import { Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Download,
    EyeOff,
    FileText,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { JsonExportButton } from '@/components/common/json-export-button';
import { Pagination } from '@/components/common/pagination';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate } from '@/lib/utils';

const CATEGORIE_LIBELLES: Record<string, string> = {
    execution: 'Exécution',
    validation: 'Validation',
    financier: 'Financier',
    suivi_evaluation: 'Suivi-Évaluation',
    autre: 'Autre',
};

function formatTaille(octets: number): string {
    if (octets < 1024) return `${octets} o`;
    if (octets < 1048576) return `${(octets / 1024).toFixed(1)} ko`;
    return `${(octets / 1048576).toFixed(1)} Mo`;
}

export default function DocumentsIndex({ documents, filters, can }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [cat, setCat] = useState(filters.categorie ?? 'all');
    const [valide, setValide] = useState(filters.valide ?? 'all');

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/documents', {
            q,
            categorie: cat === 'all' ? '' : cat,
            valide: valide === 'all' ? '' : valide,
        }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout
            pageTitle="GED — Documents de preuve"
            breadcrumbs={[{ label: 'GED Documents' }]}
            actions={
                <div className="flex gap-2">
                    <JsonExportButton data={documents.data ?? []} filename="documents" meta={{ total: documents.total ?? (documents.data?.length ?? 0), filtres: filters }} />
                    {can.upload && (
                        <Button asChild>
                            <Link href="/documents/create"><Plus className="h-4 w-4" />Déposer un document</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="GED institutionnelle"
                title="Documents de preuve"
                description="Gestion documentaire des pièces justificatives, preuves d'exécution, validations et traces associées au PAPA."
                metrics={[
                    { icon: FileText, label: 'Documents', value: Number(documents.total ?? documents.data?.length ?? 0).toLocaleString('fr-FR') },
                    { icon: CheckCircle2, label: 'Validés visibles', value: Number((documents.data ?? []).filter((d: any) => d.valide_at).length).toLocaleString('fr-FR') },
                    { icon: EyeOff, label: 'Confidentiels', value: Number((documents.data ?? []).filter((d: any) => d.confidentiel).length).toLocaleString('fr-FR') },
                    { icon: ShieldCheck, label: 'Traçabilité', value: 'GED' },
                ]}
                footer="Référentiel documentaire des preuves et validations institutionnelles"
                footerIcon={ShieldCheck}
            />

            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-5">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Libellé, nom de fichier…" className="pl-9" />
                        </div>
                        <Select value={cat} onValueChange={setCat}>
                            <SelectTrigger><SelectValue placeholder="Catégorie" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Toutes catégories</SelectItem>
                                {Object.entries(CATEGORIE_LIBELLES).map(([k, v]) => (
                                    <SelectItem key={k} value={k}>{v}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={valide} onValueChange={setValide}>
                            <SelectTrigger><SelectValue placeholder="Validation" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous</SelectItem>
                                <SelectItem value="oui">Validés</SelectItem>
                                <SelectItem value="non">En attente</SelectItem>
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
                                <TableHead>Document</TableHead>
                                <TableHead>Catégorie</TableHead>
                                <TableHead>Taille</TableHead>
                                <TableHead>Déposé le</TableHead>
                                <TableHead>Par</TableHead>
                                <TableHead>Statut</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {documents.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                        <FileText className="mx-auto mb-2 h-8 w-8 opacity-30" />
                                        Aucun document dans la GED.
                                    </TableCell>
                                </TableRow>
                            ) : documents.data.map((d: any) => (
                                <TableRow key={d.id}>
                                    <TableCell>
                                        <div className="flex items-start gap-2">
                                            <FileText className="h-4 w-4 mt-0.5 text-muted-foreground shrink-0" />
                                            <div className="min-w-0">
                                                <p className="font-medium truncate">{d.libelle}</p>
                                                <p className="font-mono text-xs text-muted-foreground truncate">{d.nom_fichier}</p>
                                                {d.confidentiel && (
                                                    <Badge variant="destructive" className="mt-1 text-[10px]">
                                                        <EyeOff className="h-3 w-3" />Confidentiel
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell><Badge variant="secondary">{CATEGORIE_LIBELLES[d.categorie] ?? d.categorie}</Badge></TableCell>
                                    <TableCell className="text-xs tabular-nums">{formatTaille(d.taille_octets)}</TableCell>
                                    <TableCell className="text-xs">{formatDate(d.created_at)}</TableCell>
                                    <TableCell className="text-xs">{d.uploade_par?.name ?? '—'}</TableCell>
                                    <TableCell>
                                        {d.valide_at ? (
                                            <Badge variant="success" className="gap-1">
                                                <CheckCircle2 className="h-3 w-3" />Validé
                                            </Badge>
                                        ) : (
                                            <Badge variant="warning">En attente</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            <Button size="sm" variant="ghost" asChild>
                                                <a href={`/documents/${d.id}/download`} title="Télécharger">
                                                    <Download className="h-4 w-4" />
                                                </a>
                                            </Button>
                                            {!d.valide_at && (
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => router.delete(`/documents/${d.id}`)}
                                                    title="Supprimer"
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <Pagination pagination={documents} label="documents" />
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}
