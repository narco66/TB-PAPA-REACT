<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification événementielle pour la chaîne de la dépense (§17 du brief).
 *
 * Couvre 11 événements :
 *   - expression.soumise        Soumission d'une expression de besoin
 *   - expression.validation_attendue  Validation hiérarchique attendue
 *   - expression.retour_correction    Retour pour correction
 *   - expression.rejet               Rejet
 *   - expression.engagee             Engagement validé
 *   - visa.accorde                   Visa financier accordé (lié à expense.validate_hierarchique)
 *   - service_fait.attendu           Service fait attendu
 *   - liquidation.validee            Liquidation validée
 *   - ordonnancement.genere          Ordre de paiement généré
 *   - paiement.effectue              Paiement effectué
 *   - echeance.depassee              Échéance de livraison dépassée
 */
class ExpenseEventNotification extends Notification
{
    use Queueable;

    public const EVENT_LABELS = [
        'expression.soumise' => 'Expression du besoin soumise',
        'expression.validation_attendue' => 'Validation hiérarchique attendue',
        'expression.retour_correction' => 'Expression retournée pour correction',
        'expression.rejet' => 'Expression rejetée',
        'expression.engagee' => 'Expression engagée budgétairement',
        'visa.accorde' => 'Visa financier accordé',
        'service_fait.attendu' => 'Service fait attendu',
        'liquidation.validee' => 'Liquidation validée',
        'ordonnancement.genere' => 'Ordre de paiement généré',
        'paiement.effectue' => 'Paiement effectué',
        'echeance.depassee' => 'Échéance de livraison dépassée',
    ];

    public function __construct(
        protected string $eventType,
        protected Model $sujet,
        protected ?User $auteur = null,
        protected ?string $commentaire = null,
        protected array $contexte = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = self::EVENT_LABELS[$this->eventType] ?? $this->eventType;
        $reference = $this->sujet->reference ?? $this->sujet->numero ?? ('#' . $this->sujet->getKey());

        return (new MailMessage)
            ->subject("[TB-PAPA-CEEAC] {$label} — {$reference}")
            ->greeting('Bonjour,')
            ->line('Un événement a été enregistré sur la chaîne de la dépense :')
            ->line("**{$label}** — {$reference}")
            ->when($this->auteur, fn ($m) => $m->line("Auteur : {$this->auteur->name}"))
            ->when($this->commentaire, fn ($m) => $m->line("Commentaire : « {$this->commentaire} »"))
            ->action("Ouvrir l'élément", $this->urlAction())
            ->salutation('Direction des Systèmes d\'Information — CEEAC');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_type' => $this->eventType,
            'event_label' => self::EVENT_LABELS[$this->eventType] ?? $this->eventType,
            'sujet_type' => $this->sujet::class,
            'sujet_id' => $this->sujet->getKey(),
            'reference' => $this->sujet->reference ?? $this->sujet->numero ?? null,
            'auteur_id' => $this->auteur?->id,
            'auteur_nom' => $this->auteur?->name,
            'commentaire' => $this->commentaire,
            'contexte' => $this->contexte,
            'url' => $this->urlAction(),
        ];
    }

    protected function urlAction(): string
    {
        return match (class_basename($this->sujet)) {
            'ExpenseRequest' => url("/expense/requests/{$this->sujet->getKey()}"),
            'BudgetMouvement' => url('/expense/service-fait'),
            'ServiceDoneCertificate', 'Reception' => url('/expense/service-fait'),
            default => url('/expense'),
        };
    }
}
