import { router } from '@inertiajs/react';
import { Activity, Search, ShieldCheck } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const EVENT_LIBELLES: Record<string, { label: string; variant: any }> = {
    created: { label: 'Créé', variant: 'success' },
    updated: { label: 'Modifié', variant: 'default' },
    deleted: { label: 'Supprimé', variant: 'destructive' },
    restored: { label: 'Restauré', variant: 'secondary' },
};

export default function AuditIndex({ logs, filters, evenements }: any) {
    const [q, setQ] = useState(filters.q ?? '');
    const [event, setEvent] = useState(filters.event ?? 'all');
    const [openId, setOpenId] = useState<number | null>(null);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.get('/admin/audit', { q, event: event === 'all' ? '' : event }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Journal d'audit"
            breadcrumbs={[{ label: 'Administration' }, { label: 'Audit' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    title="Journal d'audit"
                    description="Traçabilité des opérations sensibles et des événements institutionnels."
                />
            <Card className="mb-4 border-primary/30 bg-primary/5">
                <CardContent className="flex items-center gap-3 p-4">
                    <ShieldCheck className="h-5 w-5 text-primary" />
                    <p className="text-sm">
                        Toutes les actions critiques sont tracées de façon immuable.
                        Ce journal est consultable par les organes de contrôle et d'audit (Section 7.3 du CDC).
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="p-4">
                    <form onSubmit={submit} className="grid grid-cols-1 gap-2 md:grid-cols-4">
                        <div className="relative md:col-span-2">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Description, log…" className="pl-9" />
                        </div>
                        <Select value={event} onValueChange={setEvent}>
                            <SelectTrigger><SelectValue placeholder="Événement" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous événements</SelectItem>
                                {evenements.map((e: string) => (
                                    <SelectItem key={e} value={e}>{EVENT_LIBELLES[e]?.label ?? e}</SelectItem>
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
                                <TableHead>Date</TableHead>
                                <TableHead>Acteur</TableHead>
                                <TableHead>Événement</TableHead>
                                <TableHead>Objet</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        <Activity className="mx-auto mb-2 h-8 w-8 opacity-30" />
                                        Aucune entrée dans le journal d'audit.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                logs.data.map((log: any) => (
                                    <>
                                        <TableRow key={log.id}>
                                            <TableCell className="text-xs whitespace-nowrap">
                                                {new Date(log.created_at).toLocaleString('fr-FR')}
                                            </TableCell>
                                            <TableCell>
                                                {log.causer ? (
                                                    <>
                                                        <p className="text-sm font-medium">{log.causer.name}</p>
                                                        <p className="text-xs text-muted-foreground font-mono">{log.causer.matricule ?? `ID ${log.causer.id}`}</p>
                                                    </>
                                                ) : (
                                                    <span className="text-xs text-muted-foreground italic">Système</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {log.event && (
                                                    <Badge variant={EVENT_LIBELLES[log.event]?.variant ?? 'secondary'}>
                                                        {EVENT_LIBELLES[log.event]?.label ?? log.event}
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {log.subject_type ? (
                                                    <>
                                                        <p>{log.subject_type}</p>
                                                        <p className="font-mono text-muted-foreground">#{log.subject_id}</p>
                                                    </>
                                                ) : '—'}
                                            </TableCell>
                                            <TableCell className="text-sm">{log.description}</TableCell>
                                            <TableCell>
                                                {Object.keys(log.properties || {}).length > 0 && (
                                                    <Button size="sm" variant="ghost" onClick={() => setOpenId(openId === log.id ? null : log.id)}>
                                                        {openId === log.id ? 'Masquer' : 'Détails'}
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                        {openId === log.id && (
                                            <TableRow>
                                                <TableCell colSpan={6} className="bg-muted/30 p-4">
                                                    <pre className="overflow-x-auto rounded bg-background p-3 text-xs">
                                                        {JSON.stringify(log.properties, null, 2)}
                                                    </pre>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {logs.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                    <p>{logs.from}–{logs.to} sur {logs.total}</p>
                    <div className="flex gap-1">
                        {logs.links.map((link: any, i: number) => (
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
