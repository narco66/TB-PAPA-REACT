import { Link } from '@inertiajs/react';
import { type LucideIcon, TrendingDown, TrendingUp } from 'lucide-react';
import { type ReactNode } from 'react';
import { Card, CardContent } from '@/components/ui/card';

interface StatCardProps {
    title: string;
    value: ReactNode;
    sub?: ReactNode;
    icon: LucideIcon;
    iconBg?: string;
    trend?: number;
    href?: string;
    accent?: 'default' | 'success' | 'warning' | 'destructive' | 'info';
}

const ACCENT_BORDER: Record<string, string> = {
    default: 'border-border',
    success: 'border-emerald-200 dark:border-emerald-900/50',
    warning: 'border-amber-200 dark:border-amber-900/50',
    destructive: 'border-rose-200 dark:border-rose-900/50',
    info: 'border-sky-200 dark:border-sky-900/50',
};

export function StatCard({ title, value, sub, icon: Icon, iconBg = 'bg-primary/10 text-primary', trend, href, accent = 'default' }: StatCardProps) {
    const content = (
        <Card className={`transition-shadow hover:shadow-md ${ACCENT_BORDER[accent]} ${href ? 'cursor-pointer' : ''}`}>
            <CardContent className="flex items-center gap-4 p-5">
                <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${iconBg}`}>
                    <Icon className="h-6 w-6" />
                </div>
                <div className="flex-1 min-w-0">
                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{title}</p>
                    <p className="truncate text-2xl font-bold tabular-nums">{value}</p>
                    {(sub || trend !== undefined) && (
                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                            {trend !== undefined && (
                                <span className={`flex items-center gap-0.5 font-medium ${trend > 0 ? 'text-emerald-600' : trend < 0 ? 'text-rose-600' : ''}`}>
                                    {trend > 0 ? <TrendingUp className="h-3 w-3" /> : trend < 0 ? <TrendingDown className="h-3 w-3" /> : null}
                                    {Math.abs(trend).toFixed(1)}%
                                </span>
                            )}
                            {sub && <span className="truncate">{sub}</span>}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );

    return href ? <Link href={href}>{content}</Link> : content;
}
