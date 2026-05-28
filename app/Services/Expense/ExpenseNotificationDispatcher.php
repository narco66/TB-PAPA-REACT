<?php

namespace App\Services\Expense;

use App\Models\ExpenseRequest;
use App\Models\User;
use App\Notifications\ExpenseEventNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/**
 * Dispatcher unique des notifications de la chaîne de la dépense.
 *
 * Identifie les destinataires métier selon l'événement (par rôle/département),
 * en évitant les notifications redondantes (auteur d'un événement n'est pas notifié de son propre événement).
 */
class ExpenseNotificationDispatcher
{
    public function notifier(
        string $eventType,
        Model $sujet,
        ?User $auteur = null,
        ?string $commentaire = null,
        array $contexte = [],
    ): void {
        $destinataires = $this->resoudreDestinataires($eventType, $sujet, $auteur);

        if ($destinataires->isEmpty()) {
            return;
        }

        Notification::send(
            $destinataires,
            new ExpenseEventNotification($eventType, $sujet, $auteur, $commentaire, $contexte),
        );
    }

    /**
     * @return Collection<int, User>
     */
    protected function resoudreDestinataires(string $eventType, Model $sujet, ?User $auteur): Collection
    {
        $rolesByEvent = [
            // Workflow expression du besoin
            'expression.soumise' => ['directeur_technique', 'directeur_appui', 'commissaire', 'secretaire_general'],
            'expression.validation_attendue' => ['directeur_technique', 'directeur_appui', 'commissaire'],
            'expression.retour_correction' => null, // demandeur seul
            'expression.rejet' => null, // demandeur seul
            'expression.engagee' => ['controle_financier'], // + demandeur
            // Cycle IPSAS
            'visa.accorde' => null, // demandeur seul
            'service_fait.attendu' => ['controle_financier'],
            'liquidation.validee' => ['controle_financier'],
            'ordonnancement.genere' => ['comptable', 'controle_financier'],
            'paiement.effectue' => null, // ordonnateur + demandeur
            'echeance.depassee' => ['directeur_technique', 'directeur_appui', 'commissaire'],
        ];

        $roles = $rolesByEvent[$eventType] ?? [];
        $userIds = collect();

        if ($roles) {
            $userIds = User::role($roles)->pluck('id');
        }

        // Ajout du demandeur pour certains événements de feedback
        if ($sujet instanceof ExpenseRequest) {
            if (in_array($eventType, ['expression.retour_correction', 'expression.rejet', 'expression.engagee', 'visa.accorde', 'paiement.effectue'], true)) {
                $userIds = $userIds->merge([$sujet->demandeur_id]);
            }
            // Ajout du valideur hiérarchique pour suivi
            if (in_array($eventType, ['expression.engagee', 'paiement.effectue'], true) && $sujet->valideur_hierarchique_id) {
                $userIds = $userIds->merge([$sujet->valideur_hierarchique_id]);
            }
        }

        // Exclusion de l'auteur (on ne se notifie pas soi-même)
        if ($auteur) {
            $userIds = $userIds->reject(fn ($id) => $id === $auteur->id);
        }

        $userIds = $userIds->unique()->filter()->values();

        if ($userIds->isEmpty()) {
            return new Collection;
        }

        return User::whereIn('id', $userIds)->where('actif', true)->get();
    }
}
