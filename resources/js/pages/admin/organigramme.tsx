import { Building2, Boxes, Network, Users } from 'lucide-react';
import { AppLayout } from '@/components/layout/app-layout';
import { InstitutionalHero } from '@/components/layout/institutional-hero';
import { Card, CardContent } from '@/components/ui/card';

export default function Organigramme({ departements, stats }: any) {
    return (
        <AppLayout
            pageTitle="Organigramme"
            breadcrumbs={[{ label: 'Administration' }, { label: 'Organigramme' }]}
        >
            <div className="space-y-6">
                <InstitutionalHero
                    eyebrow="Hiérarchie institutionnelle"
                    title="Organigramme CEEAC"
                    description="Vue arborescente : Commission → Département → Direction → Service. Conforme à l'organisation officielle de la Commission de la Communauté Économique des États de l'Afrique Centrale."
                    metrics={[
                        { icon: Network, label: 'Départements', value: String(stats.departements) },
                        { icon: Building2, label: 'Directions', value: String(stats.directions) },
                        { icon: Boxes, label: 'Services', value: String(stats.services) },
                        { icon: Users, label: 'Agents', value: String(stats.agents) },
                    ]}
                />

                <div className="space-y-4">
                    {departements.map((d: any) => (
                        <Card key={d.id} className="border-l-4 border-l-ceeac-blue">
                            <CardContent className="p-5">
                                <div className="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <div className="text-xs font-bold uppercase tracking-wider text-ceeac-blue">{d.code}</div>
                                        <h2 className="text-lg font-bold mt-1">{d.libelle}</h2>
                                        {d.commissaire && (
                                            <div className="text-xs text-muted-foreground mt-1">
                                                Commissaire : <strong>{d.commissaire.name}</strong>
                                            </div>
                                        )}
                                    </div>
                                    <div className="flex gap-2 text-xs text-muted-foreground">
                                        <span className="rounded-full bg-muted px-2.5 py-1">{d.directions_count} dir.</span>
                                        <span className="rounded-full bg-muted px-2.5 py-1">{d.services_count} serv.</span>
                                        <span className="rounded-full bg-muted px-2.5 py-1">{d.axes_count} axes</span>
                                    </div>
                                </div>

                                {d.directions?.length > 0 && (
                                    <div className="pl-4 border-l-2 border-border space-y-2 mt-3">
                                        {d.directions.map((dir: any) => (
                                            <div key={dir.id} className="rounded-md bg-muted/30 p-3">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div>
                                                        <span className="text-xs font-bold text-foreground">{dir.code}</span>
                                                        <span className="text-sm ml-2">{dir.libelle}</span>
                                                        <span className={`ml-2 text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded ${dir.type === 'technique' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'}`}>
                                                            {dir.type}
                                                        </span>
                                                        {dir.directeur && (
                                                            <div className="text-[11px] text-muted-foreground mt-0.5">
                                                                Directeur : {dir.directeur.name}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>

                                                {dir.services?.length > 0 && (
                                                    <div className="pl-3 mt-2 border-l border-border/60 space-y-1">
                                                        {dir.services.map((svc: any) => (
                                                            <div key={svc.id} className="text-xs flex items-center gap-2">
                                                                <span className="font-mono text-[10px] text-muted-foreground">{svc.code}</span>
                                                                <span>{svc.libelle}</span>
                                                                {svc.chef_service && (
                                                                    <span className="text-[10px] text-muted-foreground">
                                                                        — Chef : {svc.chef_service.name}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
