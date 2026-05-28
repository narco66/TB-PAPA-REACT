import { Head } from '@inertiajs/react';
import { type PropsWithChildren, type ReactNode } from 'react';
import { FlashMessages } from './flash';
import { Sidebar } from './sidebar';
import { Topbar } from './topbar';

interface AppLayoutProps {
    title?: string;
    pageTitle?: string;
    breadcrumbs?: Array<{ label: string; href?: string }>;
    actions?: ReactNode;
}

export function AppLayout({ title, pageTitle, breadcrumbs, actions, children }: PropsWithChildren<AppLayoutProps>) {
    return (
        <div className="flex h-screen bg-background">
            <Head title={title ?? pageTitle} />
            <Sidebar />
            <div className="flex flex-1 flex-col overflow-hidden">
                <Topbar title={pageTitle ?? title} />
                <FlashMessages />
                <main className="flex-1 overflow-y-auto px-4 py-6 md:px-8">
                    {(breadcrumbs?.length || actions) && (
                        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            {breadcrumbs?.length ? (
                                <nav className="flex flex-wrap items-center gap-1 text-sm text-muted-foreground">
                                    {breadcrumbs.map((b, i) => (
                                        <span key={i} className="flex items-center gap-1">
                                            {b.href ? (
                                                <a href={b.href} className="hover:text-foreground">
                                                    {b.label}
                                                </a>
                                            ) : (
                                                <span className="text-foreground">{b.label}</span>
                                            )}
                                            {i < breadcrumbs.length - 1 && <span className="opacity-50">/</span>}
                                        </span>
                                    ))}
                                </nav>
                            ) : <span />}
                            {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
