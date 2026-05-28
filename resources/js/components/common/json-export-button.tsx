import { Braces } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface JsonExportButtonProps {
    /** Données à exporter (sera passé à JSON.stringify). Peut être un tableau ou un objet. */
    data: unknown;
    /** Nom de base du fichier (sans extension) — ex: 'utilisateurs', 'liste_axes' */
    filename: string;
    /** Métadonnées contextuelles ajoutées dans l'enveloppe (filtres actifs, totaux, etc.) */
    meta?: Record<string, unknown>;
    /** Libellé du bouton */
    label?: string;
    variant?: 'default' | 'outline' | 'ghost' | 'secondary';
    size?: 'default' | 'sm' | 'lg' | 'icon';
}

/**
 * Bouton générique d'export JSON d'une page liste.
 * Téléchargement client-side : sérialise les données déjà présentes dans les props Inertia
 * et enveloppe le tout avec des métadonnées institutionnelles (date, source, version).
 */
export function JsonExportButton({
    data,
    filename,
    meta,
    label = 'Exporter JSON',
    variant = 'outline',
    size = 'default',
}: JsonExportButtonProps) {
    const handleClick = () => {
        const enveloppe = {
            source: 'TB-PAPA · CEEAC',
            genere_le: new Date().toISOString(),
            type: filename,
            ...(meta ?? {}),
            data,
        };

        const json = JSON.stringify(enveloppe, null, 2);
        const blob = new Blob([json], { type: 'application/json;charset=utf-8' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        const stamp = new Date().toISOString().slice(0, 10);
        a.download = `${filename}_${stamp}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    return (
        <Button variant={variant} size={size} onClick={handleClick} title="Télécharger les données au format JSON">
            <Braces className="h-4 w-4" />
            {label}
        </Button>
    );
}
