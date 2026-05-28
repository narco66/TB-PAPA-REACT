import { usePage } from '@inertiajs/react';
import { FileDown } from 'lucide-react';
import { type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { PageProps } from '@/types';

interface SingleReport {
    reportKey: string;
    label?: string;
    params?: Record<string, string | number | boolean | null | undefined>;
    children?: ReactNode;
}

/**
 * Bouton PDF natif rapide. Construit l'URL /rapports/{reportKey}/quick avec les params
 * et ouvre le PDF dans un nouvel onglet (téléchargement immédiat).
 *
 * Usage :
 *   <PdfQuickButton reportKey="fiche_axe" params={{axe_id: axe.id}}>
 *     Exporter PDF
 *   </PdfQuickButton>
 */
export function PdfQuickButton({ reportKey, params = {}, children, label }: SingleReport) {
    const { auth } = usePage<PageProps>().props;
    const canGenerate = auth.user?.permissions?.includes('generate_reports') ?? false;
    if (! canGenerate) return null;

    const url = construireUrlQuick(reportKey, params);

    return (
        <Button variant="outline" size="sm" asChild>
            <a href={url} target="_blank" rel="noopener noreferrer" title="Générer un PDF institutionnel">
                <FileDown className="h-4 w-4" />
                {children ?? label ?? 'Exporter en PDF'}
            </a>
        </Button>
    );
}

interface MultiReport {
    label?: string;
    reports: Array<{
        reportKey: string;
        label: string;
        params?: Record<string, string | number | boolean | null | undefined>;
        description?: string;
    }>;
}

/**
 * Menu déroulant proposant plusieurs PDF à générer depuis la même page.
 *
 * Usage :
 *   <PdfQuickMenu reports={[
 *     { reportKey: 'fiche_axe', label: 'Fiche détaillée', params: {axe_id: axe.id} },
 *     { reportKey: 'fiche_performance_axe', label: 'Rapport de performance', params: {axe_id: axe.id} },
 *   ]} />
 */
export function PdfQuickMenu({ reports, label = 'Documents PDF' }: MultiReport) {
    const { auth } = usePage<PageProps>().props;
    const canGenerate = auth.user?.permissions?.includes('generate_reports') ?? false;
    if (! canGenerate || reports.length === 0) return null;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                    <FileDown className="h-4 w-4" />
                    {label}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-72">
                <DropdownMenuLabel>Générer un document PDF</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {reports.map((r) => (
                    <DropdownMenuItem key={r.reportKey + JSON.stringify(r.params ?? {})} asChild>
                        <a
                            href={construireUrlQuick(r.reportKey, r.params ?? {})}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex flex-col items-start gap-0.5 cursor-pointer"
                        >
                            <span className="text-sm font-medium">{r.label}</span>
                            {r.description && (
                                <span className="text-xs text-muted-foreground">{r.description}</span>
                            )}
                        </a>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function construireUrlQuick(key: string, params: Record<string, any>): string {
    const sp = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') {
            sp.append(k, String(v));
        }
    });
    const qs = sp.toString();
    return `/rapports/${key}/quick${qs ? '?' + qs : ''}`;
}
