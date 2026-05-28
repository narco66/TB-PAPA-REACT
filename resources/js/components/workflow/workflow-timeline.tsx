import { Check, CircleDot, Clock, MessageSquare, Send, ShieldCheck, XCircle } from 'lucide-react';
import { type FormEventHandler, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export interface WorkflowHistoryItem {
    id: number;
    etape: string;
    decision: string;
    commentaire: string | null;
    demandeur: string | null;
    valideur: string | null;
    date: string | null;
    date_relative: string | null;
    avant: string | null;
    apres: string | null;
}

const ETAPE_LABELS: Record<string, string> = {
    soumission: 'Soumission',
    revue_technique: 'Revue technique',
    validation_directeur: 'Validation Directeur',
    validation_commissaire: 'Validation Commissaire',
    validation_sg: 'Validation Secrétaire Général',
    validation_presidence: 'Validation Présidence',
    cloture: 'Clôture',
};

const DECISION_STYLE: Record<string, { icon: any; color: string; label: string }> = {
    en_attente: { icon: Clock, color: 'bg-amber-100 text-amber-700 border-amber-300', label: 'En attente' },
    approuve: { icon: Check, color: 'bg-emerald-100 text-emerald-700 border-emerald-300', label: 'Approuvé' },
    rejete: { icon: XCircle, color: 'bg-rose-100 text-rose-700 border-rose-300', label: 'Rejeté' },
    renvoye: { icon: Send, color: 'bg-blue-100 text-blue-700 border-blue-300', label: 'Renvoyé' },
};

const STATUT_LABELS: Record<string, string> = {
    brouillon: 'Brouillon',
    en_validation: 'En validation',
    valide: 'Validé',
    revise: 'En révision',
    cloture: 'Clôturé',
    archive: 'Archivé',
    soumis: 'Soumis',
};

const ACTION_VARIANTS: Record<string, string> = {
    submit: 'default',
    resubmit: 'default',
    approve: 'default',
    reject: 'destructive',
    revise: 'outline',
    close: 'secondary',
    archive: 'ghost',
};

export function WorkflowTimeline({ historique }: { historique: WorkflowHistoryItem[] }) {
    if (historique.length === 0) {
        return (
            <div className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground">
                <ShieldCheck className="mx-auto mb-2 h-6 w-6 opacity-40" />
                Aucune transition enregistrée. Le workflow démarrera dès la première soumission.
            </div>
        );
    }

    return (
        <ol className="relative space-y-4 border-l-2 border-muted pl-6">
            {historique.map((item, idx) => {
                const style = DECISION_STYLE[item.decision] ?? DECISION_STYLE.en_attente;
                const Icon = style.icon;
                const isLast = idx === historique.length - 1;

                return (
                    <li key={item.id} className="relative">
                        <span
                            className={`absolute -left-[31px] flex h-6 w-6 items-center justify-center rounded-full border-2 ${style.color} ${isLast ? 'ring-2 ring-offset-2 ring-primary/40' : ''}`}
                            aria-hidden
                        >
                            <Icon className="h-3 w-3" />
                        </span>
                        <div className="rounded-lg border bg-card p-3">
                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                <span className="font-medium">{ETAPE_LABELS[item.etape] ?? item.etape}</span>
                                <Badge variant="outline" className={`text-[10px] ${style.color}`}>
                                    {style.label}
                                </Badge>
                                {item.avant && item.apres && item.avant !== item.apres && (
                                    <span className="text-xs text-muted-foreground">
                                        {STATUT_LABELS[item.avant] ?? item.avant} →{' '}
                                        <strong>{STATUT_LABELS[item.apres] ?? item.apres}</strong>
                                    </span>
                                )}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Par <strong>{item.valideur ?? item.demandeur ?? 'Système'}</strong>
                                {' · '}
                                {item.date_relative ?? ''}
                                {item.date && (
                                    <span className="ml-1 opacity-70">({new Date(item.date).toLocaleString('fr-FR')})</span>
                                )}
                            </p>
                            {item.commentaire && (
                                <p className="mt-2 flex items-start gap-1.5 rounded-md bg-muted/40 p-2 text-xs italic">
                                    <MessageSquare className="h-3.5 w-3.5 mt-0.5 shrink-0 opacity-60" />« {item.commentaire} »
                                </p>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}

export interface WorkflowAction {
    key: string;
    libelle: string;
    to: string;
    permission_ok: boolean;
    permission: string;
}

interface WorkflowActionsProps {
    transitions: WorkflowAction[];
    onTransition: (action: string, commentaire: string) => void;
    processing?: boolean;
}

export function WorkflowActions({ transitions, onTransition, processing }: WorkflowActionsProps) {
    const [selected, setSelected] = useState<WorkflowAction | null>(null);
    const [commentaire, setCommentaire] = useState('');

    if (transitions.length === 0) {
        return (
            <div className="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground">
                <CircleDot className="mx-auto mb-1 h-5 w-5 opacity-40" />
                Aucune transition disponible depuis l'état actuel.
            </div>
        );
    }

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!selected) return;
        onTransition(selected.key, commentaire);
        setSelected(null);
        setCommentaire('');
    };

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap gap-2">
                {transitions.map((t) => (
                    <Button
                        key={t.key}
                        type="button"
                        size="sm"
                        variant={(ACTION_VARIANTS[t.key] as any) ?? 'default'}
                        disabled={!t.permission_ok || processing}
                        onClick={() => setSelected(t)}
                        title={t.permission_ok ? undefined : `Permission requise : ${t.permission}`}
                    >
                        {t.libelle}
                    </Button>
                ))}
            </div>

            {selected && (
                <form onSubmit={submit} className="space-y-2 rounded-md border bg-muted/30 p-3">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="commentaire" className="text-sm font-medium">
                            {selected.libelle}
                        </Label>
                        <Button type="button" variant="ghost" size="sm" onClick={() => setSelected(null)}>
                            Annuler
                        </Button>
                    </div>
                    <Textarea
                        id="commentaire"
                        rows={3}
                        value={commentaire}
                        onChange={(e) => setCommentaire(e.target.value)}
                        placeholder={selected.key === 'reject' ? 'Motif du rejet (obligatoire en pratique)…' : 'Commentaire optionnel…'}
                        maxLength={2000}
                    />
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing} size="sm" variant={selected.key === 'reject' ? 'destructive' : 'default'}>
                            Confirmer
                        </Button>
                    </div>
                </form>
            )}
        </div>
    );
}
