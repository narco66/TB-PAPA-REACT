import { Link } from '@inertiajs/react';
import { BarChart3, ClipboardList, FileBarChart, FileText, GitBranch, LayoutDashboard, PiggyBank, ShieldCheck, Target, TrendingUp, Users } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/utils';

const ICONES: Record<string, any> = {
    FileText, ClipboardList, BarChart3, GitBranch, Target,
    ShieldCheck, LayoutDashboard, PiggyBank, TrendingUp, Users, FileBarChart,
};

const COULEURS_CATEGORIE: Record<string, string> = {
    strategique: 'bg-blue-100 text-blue-700',
    budget: 'bg-emerald-100 text-emerald-700',
    performance: 'bg-violet-100 text-violet-700',
    rbm: 'bg-amber-100 text-amber-700',
    gouvernance: 'bg-indigo-100 text-indigo-700',
    audit: 'bg-red-100 text-red-700',
    analytique: 'bg-sky-100 text-sky-700',
    operationnel: 'bg-teal-100 text-teal-700',
    ptf: 'bg-orange-100 text-orange-700',
    ged: 'bg-gray-100 text-gray-700',
};

export default function ReportsIndex({ categories, historique }: any) {
    return (
        <AppLayout
            pageTitle="Rapports & Documents institutionnels"
            breadcrumbs={[{ label: 'Rapports' }]}
            actions={
                <Button variant="outline" asChild>
                    <Link href="/rapports/historique"><FileText className="h-4 w-4" />Historique</Link>
                </Button>
            }
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Documents institutionnels"
                    title="Rapports & Documents institutionnels"
                    description="Génération de rapports stratégiques, budgétaires, RBM/GAR, gouvernance et audit avec traçabilité et vérification."
                    metrics={[
                        { icon: FileBarChart, label: 'Catégories', value: Number(categories.length ?? 0).toLocaleString('fr-FR') },
                        { icon: FileText, label: 'Modèles', value: categories.reduce((sum: number, cat: any) => sum + Number(cat.rapports?.length ?? 0), 0).toLocaleString('fr-FR') },
                        { icon: ShieldCheck, label: 'Vérification', value: 'QR' },
                        { icon: TrendingUp, label: 'Récents', value: Number(historique.length ?? 0).toLocaleString('fr-FR') },
                    ]}
                    footer="Standards : RBM/GAR · COSO · IPSAS · ISO 27001 · COBIT 2019"
                    footerIcon={ShieldCheck}
                />

                <Card className="border-primary/30 bg-gradient-to-r from-primary/5 to-transparent">
                    <CardContent className="p-6">
                        <h2 className="text-lg font-semibold mb-2">Moteur de génération PDF institutionnel</h2>
                        <p className="text-sm text-muted-foreground">
                            Génération automatique de rapports stratégiques, budgétaires, RBM/GAR, de gouvernance et d'audit
                            conformes aux standards internationaux (RBM/GAR · COSO · IPSAS · ISO 27001 · COBIT 2019).
                            Chaque document est signé par un code de vérification + QR Code pour assurer son authenticité.
                        </p>
                    </CardContent>
                </Card>

                {categories.map((cat: any) => (
                    <div key={cat.key}>
                        <div className="mb-3 flex items-center gap-2">
                            <Badge className={COULEURS_CATEGORIE[cat.key] ?? ''}>{cat.libelle}</Badge>
                            <span className="text-sm text-muted-foreground">— {cat.rapports.length} rapport(s)</span>
                        </div>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                            {cat.rapports.map((r: any) => {
                                const Icone = ICONES[r.icone] ?? FileText;
                                return (
                                    <Link key={r.key} href={`/rapports/${r.key}/generer`}>
                                        <Card className="h-full transition-shadow hover:shadow-md cursor-pointer">
                                            <CardHeader className="pb-3">
                                                <div className="flex items-start gap-3">
                                                    <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${COULEURS_CATEGORIE[cat.key] ?? 'bg-muted'}`}>
                                                        <Icone className="h-5 w-5" />
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <CardTitle className="text-sm">{r.titre}</CardTitle>
                                                    </div>
                                                </div>
                                            </CardHeader>
                                            <CardContent>
                                                <CardDescription className="text-xs line-clamp-3">{r.description}</CardDescription>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                ))}

                {historique.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Derniers rapports générés</CardTitle>
                            <CardDescription>Les 20 plus récents</CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-xs uppercase">
                                    <tr>
                                        <th className="text-left p-3">Rapport</th>
                                        <th className="text-left p-3">Catégorie</th>
                                        <th className="text-left p-3">Émis par</th>
                                        <th className="text-left p-3">Date</th>
                                        <th className="text-right p-3">Téléchargements</th>
                                        <th className="text-right p-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {historique.map((h: any) => (
                                        <tr key={h.id} className="border-t">
                                            <td className="p-3 font-medium">{h.titre}</td>
                                            <td className="p-3">
                                                <Badge className={COULEURS_CATEGORIE[h.categorie] ?? ''}>{h.categorie}</Badge>
                                            </td>
                                            <td className="p-3 text-xs">{h.genere_par ?? '—'}</td>
                                            <td className="p-3 text-xs">{formatDate(h.genere_at)}</td>
                                            <td className="p-3 text-right tabular-nums">{h.nb_telechargements}</td>
                                            <td className="p-3 text-right">
                                                <Button size="sm" variant="ghost" asChild>
                                                    <a href={`/rapports/${h.id}/telecharger`}>PDF</a>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
