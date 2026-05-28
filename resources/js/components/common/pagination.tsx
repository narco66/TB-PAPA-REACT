import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * Composant de pagination réutilisable pour les pages Inertia avec
 * un paginateur Laravel (`->paginate()->withQueryString()`).
 *
 * Affiche : "Affichage de X à Y sur Z" + navigation First/Prev/Pages/Next/Last
 * Respecte les filtres en query string (links).
 */
interface PaginatedData {
    data: unknown[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface PaginationProps {
    pagination: PaginatedData;
    /** Libellé de l'unité paginée (ex: "utilisateurs", "lignes"). Défaut : "résultats". */
    label?: string;
    /** Nombre max de boutons numéros visibles (hors ellipsis). Défaut : 7. */
    maxButtons?: number;
}

export function Pagination({ pagination, label = 'résultats', maxButtons = 7 }: PaginationProps) {
    if (!pagination || pagination.last_page <= 1) {
        // 1 seule page : on affiche juste le résumé
        return (
            <div className="flex items-center justify-between px-4 py-3 border-t text-sm text-muted-foreground">
                <div>
                    {pagination?.total > 0 ? (
                        <>Affichage de <strong>{pagination.from}</strong> à <strong>{pagination.to}</strong> sur <strong>{pagination.total}</strong> {label}</>
                    ) : (
                        <>Aucun {label.replace(/s$/, '')}</>
                    )}
                </div>
            </div>
        );
    }

    const { current_page, last_page, from, to, total } = pagination;

    // Calcul des numéros de page à afficher avec ellipsis
    const pageNumbers: (number | 'ellipsis')[] = [];
    const halfMax = Math.floor(maxButtons / 2);

    if (last_page <= maxButtons) {
        for (let i = 1; i <= last_page; i++) pageNumbers.push(i);
    } else {
        pageNumbers.push(1);
        let start = Math.max(2, current_page - halfMax + 1);
        let end = Math.min(last_page - 1, current_page + halfMax - 1);

        if (current_page <= halfMax) {
            end = maxButtons - 2;
        } else if (current_page >= last_page - halfMax) {
            start = last_page - maxButtons + 3;
        }

        if (start > 2) pageNumbers.push('ellipsis');
        for (let i = start; i <= end; i++) pageNumbers.push(i);
        if (end < last_page - 1) pageNumbers.push('ellipsis');
        pageNumbers.push(last_page);
    }

    const firstUrl = pagination.links[0]?.url;
    const lastUrl = pagination.links[pagination.links.length - 1]?.url;
    const prevUrl = pagination.links.find((l) => l.label.includes('Previous') || l.label.includes('Précédent'))?.url;
    const nextUrl = pagination.links.find((l) => l.label.includes('Next') || l.label.includes('Suivant'))?.url;

    const pageUrl = (page: number) => pagination.links.find((l) => l.label === String(page))?.url;

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 py-3 border-t">
            <p className="text-sm text-muted-foreground">
                Affichage de <strong className="text-foreground tabular-nums">{from}</strong>{' '}
                à <strong className="text-foreground tabular-nums">{to}</strong> sur{' '}
                <strong className="text-foreground tabular-nums">{total.toLocaleString('fr-FR')}</strong> {label}
            </p>

            <nav className="flex items-center gap-1" aria-label="Pagination">
                {/* First page */}
                <Button asChild={!!firstUrl} variant="outline" size="icon" className="h-8 w-8" disabled={current_page === 1 || !firstUrl}>
                    {firstUrl && current_page > 1 ? (
                        <Link href={firstUrl} preserveState preserveScroll aria-label="Première page">
                            <ChevronsLeft className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span><ChevronsLeft className="h-4 w-4" /></span>
                    )}
                </Button>

                {/* Previous */}
                <Button asChild={!!prevUrl} variant="outline" size="icon" className="h-8 w-8" disabled={!prevUrl}>
                    {prevUrl ? (
                        <Link href={prevUrl} preserveState preserveScroll aria-label="Page précédente">
                            <ChevronLeft className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span><ChevronLeft className="h-4 w-4" /></span>
                    )}
                </Button>

                {/* Page numbers */}
                <div className="hidden sm:flex items-center gap-1">
                    {pageNumbers.map((p, idx) => {
                        if (p === 'ellipsis') {
                            return <span key={`e-${idx}`} className="px-2 text-muted-foreground">…</span>;
                        }
                        const url = pageUrl(p);
                        const isActive = p === current_page;
                        return (
                            <Button
                                key={p}
                                asChild={!!url && !isActive}
                                variant={isActive ? 'default' : 'outline'}
                                size="sm"
                                className={cn('h-8 min-w-[2rem] tabular-nums', isActive && 'pointer-events-none')}
                                aria-current={isActive ? 'page' : undefined}
                            >
                                {url && !isActive ? (
                                    <Link href={url} preserveState preserveScroll>{p}</Link>
                                ) : (
                                    <span>{p}</span>
                                )}
                            </Button>
                        );
                    })}
                </div>

                {/* Mobile : page X / Y */}
                <span className="sm:hidden text-sm px-2 tabular-nums">
                    {current_page} / {last_page}
                </span>

                {/* Next */}
                <Button asChild={!!nextUrl} variant="outline" size="icon" className="h-8 w-8" disabled={!nextUrl}>
                    {nextUrl ? (
                        <Link href={nextUrl} preserveState preserveScroll aria-label="Page suivante">
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span><ChevronRight className="h-4 w-4" /></span>
                    )}
                </Button>

                {/* Last page */}
                <Button asChild={!!lastUrl} variant="outline" size="icon" className="h-8 w-8" disabled={current_page === last_page || !lastUrl}>
                    {lastUrl && current_page < last_page ? (
                        <Link href={lastUrl} preserveState preserveScroll aria-label="Dernière page">
                            <ChevronsRight className="h-4 w-4" />
                        </Link>
                    ) : (
                        <span><ChevronsRight className="h-4 w-4" /></span>
                    )}
                </Button>
            </nav>
        </div>
    );
}
