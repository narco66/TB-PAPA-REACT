import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Info, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

export function FlashMessages() {
    const { flash } = usePage<PageProps>().props;
    const [visible, setVisible] = useState<Record<string, boolean>>({});

    useEffect(() => {
        const next: Record<string, boolean> = {};
        Object.entries(flash || {}).forEach(([k, v]) => { if (v) next[k] = true; });
        setVisible(next);
        const t = setTimeout(() => setVisible({}), 6000);
        return () => clearTimeout(t);
    }, [flash]);

    const messages: Array<{ key: keyof typeof flash; icon: typeof CheckCircle2; classes: string }> = [
        { key: 'success', icon: CheckCircle2, classes: 'border-success/40 bg-success/10 text-success-foreground' },
        { key: 'error', icon: AlertCircle, classes: 'border-destructive/40 bg-destructive/10 text-destructive' },
        { key: 'warning', icon: AlertCircle, classes: 'border-warning/40 bg-warning/10 text-foreground' },
        { key: 'info', icon: Info, classes: 'border-primary/40 bg-primary/10 text-foreground' },
    ];

    return (
        <div className="pointer-events-none fixed top-20 right-4 z-50 flex w-full max-w-sm flex-col gap-2">
            {messages.map(({ key, icon: Icon, classes }) => {
                if (!flash?.[key] || !visible[key]) return null;
                return (
                    <div
                        key={key}
                        className={cn(
                            'pointer-events-auto flex items-start gap-3 rounded-lg border bg-card p-3 shadow-md',
                            classes,
                        )}
                    >
                        <Icon className="h-5 w-5 shrink-0 mt-0.5" />
                        <p className="flex-1 text-sm">{flash[key]}</p>
                        <button
                            onClick={() => setVisible((v) => ({ ...v, [key]: false }))}
                            className="opacity-50 hover:opacity-100"
                            type="button"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
