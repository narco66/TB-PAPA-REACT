import { FileDown } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PdfExportButtonProps {
    /** Clé du rapport (ex: 'liste_axes', 'liste_activites') */
    reportKey: string;
    /** Filtres à passer en query string (ex: { statut: 'valide', q: 'paix' }) */
    filtres?: Record<string, string | number | boolean | null | undefined>;
    /** Libellé du bouton */
    label?: string;
    /** Variante visuelle */
    variant?: 'default' | 'outline' | 'ghost' | 'secondary';
    /** Taille */
    size?: 'default' | 'sm' | 'lg' | 'icon';
}

/**
 * Bouton générique d'export PDF d'une page liste.
 * Construit une URL vers /rapports/{key}/quick avec les filtres en query string.
 * Le serveur génère le PDF, l'archive et le sert inline.
 */
export function PdfExportButton({
    reportKey,
    filtres,
    label = 'Exporter PDF',
    variant = 'outline',
    size = 'default',
}: PdfExportButtonProps) {
    const params = new URLSearchParams();
    if (filtres) {
        for (const [k, v] of Object.entries(filtres)) {
            if (v !== undefined && v !== null && v !== '' && v !== false) {
                params.set(k, String(v));
            }
        }
    }
    const qs = params.toString();
    const href = `/rapports/${reportKey}/quick${qs ? '?' + qs : ''}`;

    return (
        <Button variant={variant} size={size} asChild>
            <a href={href} target="_blank" rel="noopener noreferrer">
                <FileDown className="h-4 w-4" />
                {label}
            </a>
        </Button>
    );
}
