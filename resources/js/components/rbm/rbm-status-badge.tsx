import { Badge } from '@/components/ui/badge';

const LIBELLES: Record<string, string> = {
    brouillon: 'Brouillon',
    soumis: 'Soumis',
    en_validation: 'En validation',
    valide: 'Validé',
    rejete: 'Rejeté',
    archive: 'Archivé',
    planifiee: 'Planifiée',
    en_cours: 'En cours',
    realisee: 'Réalisée',
    suspendue: 'Suspendue',
    annulee: 'Annulée',
};

const VARIANTS: Record<string, 'default' | 'secondary' | 'success' | 'warning' | 'destructive' | 'outline'> = {
    brouillon: 'secondary',
    soumis: 'warning',
    en_validation: 'warning',
    valide: 'success',
    rejete: 'destructive',
    archive: 'outline',
    planifiee: 'secondary',
    en_cours: 'default',
    realisee: 'success',
    suspendue: 'warning',
    annulee: 'destructive',
};

export function RbmStatusBadge({ statut }: { statut: string }) {
    return <Badge variant={VARIANTS[statut] ?? 'outline'}>{LIBELLES[statut] ?? statut}</Badge>;
}

export function TauxBadge({ taux }: { taux: number }) {
    const variant: any =
        taux >= 75 ? 'success' :
        taux >= 40 ? 'default' :
        taux > 0 ? 'warning' : 'secondary';
    return <Badge variant={variant} className="tabular-nums">{Math.round(taux)}%</Badge>;
}
