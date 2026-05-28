import { FileSpreadsheet, ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';

interface ExcelExportButtonProps {
    /** Journal cible (ex: 'expressions', 'engagements', 'paiements', 'suppliers') */
    journal: string;
    /** Filtres à passer en query string */
    filtres?: Record<string, string | number | boolean | null | undefined>;
    /** Libellé du bouton */
    label?: string;
    /** Variante */
    variant?: 'default' | 'outline' | 'ghost' | 'secondary';
    /** Taille */
    size?: 'default' | 'sm' | 'lg' | 'icon';
}

/**
 * Bouton dropdown d'export Excel / CSV pour un journal de la chaîne de la dépense.
 */
export function ExcelExportButton({ journal, filtres, label = 'Exporter', variant = 'outline', size = 'default' }: ExcelExportButtonProps) {
    const [open, setOpen] = useState(false);

    const buildHref = (format: 'xlsx' | 'csv') => {
        const params = new URLSearchParams();
        if (filtres) {
            for (const [k, v] of Object.entries(filtres)) {
                if (v !== undefined && v !== null && v !== '' && v !== false) params.set(k, String(v));
            }
        }
        const qs = params.toString();
        return `/expense/exports/${journal}.${format}${qs ? '?' + qs : ''}`;
    };

    return (
        <div className="relative inline-block">
            <Button variant={variant} size={size} onClick={() => setOpen(!open)} type="button">
                <FileSpreadsheet className="h-4 w-4" />
                {label}
                <ChevronDown className="h-3 w-3" />
            </Button>
            {open && (
                <>
                    <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
                    <div className="absolute right-0 mt-1 w-44 rounded-md border bg-card shadow-lg z-50">
                        <a
                            href={buildHref('xlsx')}
                            className="block px-4 py-2 text-sm hover:bg-accent transition-colors"
                            onClick={() => setOpen(false)}
                        >
                            <FileSpreadsheet className="inline h-4 w-4 mr-2 text-emerald-600" />
                            Excel (.xlsx)
                        </a>
                        <a
                            href={buildHref('csv')}
                            className="block px-4 py-2 text-sm hover:bg-accent transition-colors border-t"
                            onClick={() => setOpen(false)}
                        >
                            <FileSpreadsheet className="inline h-4 w-4 mr-2 text-blue-600" />
                            CSV (.csv)
                        </a>
                    </div>
                </>
            )}
        </div>
    );
}
