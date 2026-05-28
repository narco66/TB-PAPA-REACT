<?php

namespace App\Console\Commands;

use App\Models\Budget\BudgetMouvement;
use App\Models\ExpenseRequest;
use App\Services\Expense\ExpenseNotificationDispatcher;
use Illuminate\Console\Command;

/**
 * Détecte les expressions du besoin engagées dont la date de livraison souhaitée est dépassée,
 * sans paiement effectué — et déclenche une notification 'echeance.depassee'.
 *
 * À programmer en cron quotidien :
 *   $schedule->command('expense:detecter-echeances')->dailyAt('07:00');
 */
class DetecterEcheancesExpenseCommand extends Command
{
    protected $signature = 'expense:detecter-echeances
        {--dry-run : N\'envoie aucune notification, affiche seulement les détections}';

    protected $description = 'Détecte et notifie les engagements dont l\'échéance de livraison est dépassée.';

    public function handle(ExpenseNotificationDispatcher $dispatcher): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Engagements sans paiement effectué, échéance dépassée
        $candidats = ExpenseRequest::where('statut', 'engage')
            ->whereNotNull('date_livraison_souhaitee')
            ->whereDate('date_livraison_souhaitee', '<', now())
            ->with(['demandeur', 'engagement'])
            ->get()
            ->filter(function ($r) {
                if (! $r->engagement) {
                    return true;
                }
                // Si déjà payé, on n'alerte pas
                $paye = BudgetMouvement::where('parent_mouvement_id', $r->engagement->id)
                    ->orWhere(function ($q) use ($r) {
                        $q->whereHas('parent.parent', fn ($x) => $x->where('id', $r->engagement->id))
                            ->orWhereHas('parent.parent.parent', fn ($x) => $x->where('id', $r->engagement->id));
                    })
                    ->where('type', 'paiement')
                    ->where('statut_mouvement', 'valide')
                    ->exists();

                return ! $paye;
            });

        $this->info("Détections : {$candidats->count()} expression(s) en retard.");

        foreach ($candidats as $r) {
            $jours = (int) abs(now()->diffInDays($r->date_livraison_souhaitee));
            $this->line(" - {$r->numero} ({$r->objet}) — {$jours}j de retard");

            if (! $dryRun) {
                $dispatcher->notifier(
                    'echeance.depassee',
                    $r,
                    null,
                    "Échéance dépassée de {$jours} jour(s).",
                    ['jours_retard' => $jours, 'date_echeance' => $r->date_livraison_souhaitee?->format('Y-m-d')],
                );
            }
        }

        if ($dryRun) {
            $this->warn('Mode dry-run : aucune notification envoyée.');
        } else {
            $this->info('Notifications envoyées.');
        }

        return self::SUCCESS;
    }
}
