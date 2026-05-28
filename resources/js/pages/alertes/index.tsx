import { router, useForm } from '@inertiajs/react';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, RefreshCw, ShieldCheck, Zap } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { AppLayout } from '@/components/layout/app-layout';
import { PdfExportButton } from '@/components/common/pdf-export-button';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn, formatDate } from '@/lib/utils';

const NIVEAU_ICONS: Record<string, any> = {
    info: Info,
    attention: AlertCircle,
    critique: AlertTriangle,
};
const NIVEAU_CLASSES: Record<string, string> = {
    info: 'text-primary bg-primary/10',
    attention: 'text-warning-foreground bg-warning/15',
    critique: 'text-destructive bg-destructive/10',
};

const CATEGORIE_LIBELLES: Record<string, string> = {
    retard: 'Retard',
    derive_budgetaire: 'Dérive budgétaire',
    sous_performance: 'Sous-performance',
    incoherence: 'Incohérence',
    risque: 'Risque',
    autre: 'Autre',
};

export default function AlertesIndex({ alertes, filters, stats, can }: any) {
    const [openId, setOpenId] = useState<number | null>(null);

    const filtrer = (k: string, v: string) => {
        router.get('/alertes', { ...filters, [k]: v === 'all' ? '' : v }, { preserveState: true });
    };

    return (
        <AppLayout
            pageTitle="Alertes & Risques"
            breadcrumbs={[{ label: 'Alertes' }]}
            actions={
                <>
                    <PdfExportButton reportKey="liste_alertes" filtres={filters} />
                    {can.detect && (
                        <Button onClick={() => router.post('/alertes/detecter')}>
                            <Zap className="h-4 w-4" />Détecter les alertes
                        </Button>
                    )}
                </>
            }
        >
            <div className="space-y-6">
            <InstitutionalHero
                eyebrow="Supervision institutionnelle"
                title="Alertes & Risques"
                description="Détection, qualification et traitement des alertes de retard, sous-performance, incohérence et risque opérationnel."
                metrics={[
                    { icon: AlertCircle, label: 'Ouvertes', value: Number(stats.ouvertes ?? 0).toLocaleString('fr-FR') },
                    { icon: RefreshCw, label: 'En traitement', value: Number(stats.en_traitement ?? 0).toLocaleString('fr-FR') },
                    { icon: AlertTriangle, label: 'Critiques', value: Number(stats.critiques ?? 0).toLocaleString('fr-FR') },
                    { icon: CheckCircle2, label: 'Résolues 30j', value: Number(stats.resolues_30j ?? 0).toLocaleString('fr-FR') },
                ]}
                footer="Pilotage des risques et traçabilité des actions correctives"
                footerIcon={ShieldCheck}
            />

            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                <StatCard label="Ouvertes" value={stats.ouvertes} icon={AlertCircle} color="text-warning-foreground bg-warning/15" />
                <StatCard label="En traitement" value={stats.en_traitement} icon={RefreshCw} color="text-primary bg-primary/10" />
                <StatCard label="Critiques" value={stats.critiques} icon={AlertTriangle} color="text-destructive bg-destructive/10" />
                <StatCard label="Résolues (30j)" value={stats.resolues_30j} icon={CheckCircle2} color="text-success bg-success/10" />
            </div>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                    <CardTitle className="text-base">Liste des alertes</CardTitle>
                    <div className="flex gap-2">
                        <Select value={filters.niveau || 'all'} onValueChange={(v) => filtrer('niveau', v)}>
                            <SelectTrigger className="w-36"><SelectValue placeholder="Niveau" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Tous niveaux</SelectItem>
                                <SelectItem value="info">Info</SelectItem>
                                <SelectItem value="attention">Attention</SelectItem>
                                <SelectItem value="critique">Critique</SelectItem>
                            </SelectContent>
                        </Select>
                        <Select value={filters.categorie || 'all'} onValueChange={(v) => filtrer('categorie', v)}>
                            <SelectTrigger className="w-48"><SelectValue placeholder="Catégorie" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Toutes catégories</SelectItem>
                                {Object.entries(CATEGORIE_LIBELLES).map(([k, v]) => (
                                    <SelectItem key={k} value={k}>{v}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={filters.statut || 'all'} onValueChange={(v) => filtrer('statut', v)}>
                            <SelectTrigger className="w-40"><SelectValue placeholder="Statut" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Ouvertes</SelectItem>
                                <SelectItem value="ouverte">Ouverte</SelectItem>
                                <SelectItem value="en_traitement">En traitement</SelectItem>
                                <SelectItem value="resolue">Résolue</SelectItem>
                                <SelectItem value="ignoree">Ignorée</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent className="space-y-2">
                    {alertes.data.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            Aucune alerte ouverte. La supervision du PAPA est sereine.
                        </p>
                    ) : alertes.data.map((a: any) => {
                        const Icon = NIVEAU_ICONS[a.niveau] ?? Info;
                        return (
                            <div key={a.id} className="rounded-lg border bg-card">
                                <div className="flex items-start gap-3 p-4">
                                    <div className={cn('rounded-md p-2', NIVEAU_CLASSES[a.niveau])}>
                                        <Icon className="h-5 w-5" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex flex-wrap items-center gap-2 mb-1">
                                            <Badge variant="outline">{CATEGORIE_LIBELLES[a.categorie] ?? a.categorie}</Badge>
                                            <Badge variant={a.niveau === 'critique' ? 'destructive' : a.niveau === 'attention' ? 'warning' : 'secondary'}>
                                                {a.niveau}
                                            </Badge>
                                            <span className="text-xs text-muted-foreground">{formatDate(a.created_at)}</span>
                                            {a.automatique && <Badge variant="secondary" className="text-[10px]">Auto</Badge>}
                                        </div>
                                        <p className="font-semibold text-sm">{a.titre}</p>
                                        <p className="text-sm text-muted-foreground mt-1">{a.message}</p>
                                    </div>
                                    {can.resolve && a.statut !== 'resolue' && a.statut !== 'ignoree' && (
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => setOpenId(openId === a.id ? null : a.id)}
                                        >
                                            Traiter
                                        </Button>
                                    )}
                                </div>
                                {openId === a.id && <ResoudreForm alerte={a} onClose={() => setOpenId(null)} />}
                            </div>
                        );
                    })}
                </CardContent>
            </Card>
            </div>
        </AppLayout>
    );
}

function ResoudreForm({ alerte, onClose }: { alerte: any; onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm({
        action_corrective: '',
        statut: 'resolue' as 'resolue' | 'ignoree',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/alertes/${alerte.id}/resolve`, { onSuccess: onClose });
    };

    return (
        <form onSubmit={submit} className="border-t bg-muted/30 p-4 space-y-3">
            <div className="space-y-1">
                <label className="text-sm font-medium">Action corrective *</label>
                <Textarea value={data.action_corrective} onChange={(e) => setData('action_corrective', e.target.value)} rows={3} required placeholder="Décrire l'action prise ou la justification du traitement." />
                {errors.action_corrective && <p className="text-xs text-destructive">{errors.action_corrective}</p>}
            </div>
            <div className="flex items-center justify-end gap-2">
                <Select value={data.statut} onValueChange={(v) => setData('statut', v as any)}>
                    <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="resolue">Résoudre</SelectItem>
                        <SelectItem value="ignoree">Ignorer</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="button" variant="ghost" size="sm" onClick={onClose}>Annuler</Button>
                <Button type="submit" size="sm" disabled={processing}>Enregistrer</Button>
            </div>
        </form>
    );
}

function StatCard({ label, value, icon: Icon, color }: { label: string; value: number; icon: any; color: string }) {
    return (
        <Card>
            <CardContent className="flex items-center gap-3 p-4">
                <div className={cn('flex h-10 w-10 items-center justify-center rounded-lg', color)}>
                    <Icon className="h-5 w-5" />
                </div>
                <div>
                    <p className="text-2xl font-bold tabular-nums">{value}</p>
                    <p className="text-xs text-muted-foreground">{label}</p>
                </div>
            </CardContent>
        </Card>
    );
}
