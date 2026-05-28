import { BarChart3, type LucideIcon } from 'lucide-react';
import { Card } from '@/components/ui/card';

interface HeroMetric {
    icon: LucideIcon;
    label: string;
    value: string | number;
}

interface InstitutionalHeroProps {
    eyebrow?: string;
    title: string;
    description: string;
    metrics?: HeroMetric[];
    footer?: string;
    footerIcon?: LucideIcon;
}

export function InstitutionalHero({
    eyebrow = 'Commission de la CEEAC',
    title,
    description,
    metrics = [],
    footer,
    footerIcon: FooterIcon = BarChart3,
}: InstitutionalHeroProps) {
    return (
        <Card className="overflow-hidden border-primary/30">
            <div className="relative bg-linear-to-br from-ceeac-blue via-ceeac-blue/90 to-ceeac-blue/70 px-6 py-7 text-white">
                <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 20% 20%, white 1px, transparent 1px)', backgroundSize: '24px 24px' }} />
                <div className="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex items-center gap-5">
                        <img src="/images/LOGO-CEEAC.jpg" alt="CEEAC" className="h-20 w-20 rounded-xl bg-white p-1 object-contain shadow-lg" />
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">{eyebrow}</p>
                            <h1 className="text-2xl font-bold leading-tight md:text-3xl">{title}</h1>
                            <p className="mt-1 max-w-2xl text-sm text-white/80">{description}</p>
                        </div>
                    </div>
                    {metrics.length > 0 && (
                        <div className="grid grid-cols-2 gap-3 rounded-xl bg-white/10 p-4 backdrop-blur-sm sm:grid-cols-4 lg:min-w-[520px]">
                            {metrics.map(({ icon: Icon, label, value }) => (
                                <div key={label} className="min-w-0">
                                    <div className="mb-2 flex items-center gap-2 text-white/70">
                                        <Icon className="h-4 w-4 shrink-0" />
                                        <p className="truncate text-[10px] font-semibold uppercase tracking-wider">{label}</p>
                                    </div>
                                    <p className="text-2xl font-bold tabular-nums leading-none">{value}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
            {footer && (
                <div className="border-t bg-muted/30 px-6 py-4">
                    <div className="flex items-center gap-2 text-sm font-medium">
                        <FooterIcon className="h-4 w-4 text-primary" />
                        <span>{footer}</span>
                    </div>
                </div>
            )}
        </Card>
    );
}
