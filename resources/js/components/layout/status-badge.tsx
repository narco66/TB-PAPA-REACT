import { Badge } from '@/components/ui/badge';
import type { StatutPapa } from '@/types';

const LIBELLES: Record<string, string> = {
    brouillon: 'Brouillon',
    en_validation: 'En validation',
    valide: 'Validé',
    revise: 'Révisé',
    cloture: 'Clôturé',
    archive: 'Archivé',
    proposee: 'Proposée',
    validee: 'Validée',
    en_cours: 'En cours',
    realisee: 'Réalisée',
    suspendue: 'Suspendue',
    annulee: 'Annulée',
};

const VARIANTS: Record<string, 'default' | 'secondary' | 'success' | 'warning' | 'destructive' | 'outline'> = {
    brouillon: 'secondary',
    en_validation: 'warning',
    valide: 'success',
    revise: 'warning',
    cloture: 'outline',
    archive: 'outline',
    proposee: 'secondary',
    validee: 'success',
    en_cours: 'default',
    realisee: 'success',
    suspendue: 'warning',
    annulee: 'destructive',
};

export function StatusBadge({ statut }: { statut: StatutPapa | string }) {
    const libelle = LIBELLES[statut] ?? statut;
    const variant = VARIANTS[statut] ?? 'outline';
    return <Badge variant={variant}>{libelle}</Badge>;
}

export function PrioriteBadge({ priorite }: { priorite: string }) {
    const map: Record<string, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'warning' }> = {
        haute: { label: 'Haute', variant: 'destructive' },
        moyenne: { label: 'Moyenne', variant: 'warning' },
        basse: { label: 'Basse', variant: 'secondary' },
    };
    const c = map[priorite] ?? { label: priorite, variant: 'secondary' as const };
    return <Badge variant={c.variant}>{c.label}</Badge>;
}

export function TypeBadge({ type }: { type: 'technique' | 'appui_soutien' | string }) {
    const libelle = type === 'technique' ? 'Technique' : type === 'appui_soutien' ? 'Appui & Soutien' : type;
    return <Badge variant="outline">{libelle}</Badge>;
}
