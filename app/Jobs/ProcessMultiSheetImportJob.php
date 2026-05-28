<?php

namespace App\Jobs;

use App\Models\Budget\BudgetExercice;
use App\Services\Budget\Import\MultiSheetImportOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job asynchrone : exécute un import multi-feuilles en arrière-plan.
 *
 * Utile pour les fichiers > 5 000 lignes qui dépasseraient le timeout HTTP.
 * Le statut peut être suivi via GET /budget/imports/{import}/statut.
 */
class ProcessMultiSheetImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Pas de réessai automatique pour un import — la cohérence référentielle l'interdit. */
    public int $tries = 1;

    /** Timeout 30 min pour gros fichiers. */
    public int $timeout = 1800;

    public function __construct(
        public int $exerciceId,
        public string $cheminFichier,
        public string $fichierNom,
        public int $tailleOctets,
        public string $hashSha256,
        public int $userId,
        public array $feuilles,
        public ?int $papaId,
        public bool $dryRun,
    ) {}

    public function handle(MultiSheetImportOrchestrator $orchestrator): void
    {
        $exercice = BudgetExercice::find($this->exerciceId);
        if (! $exercice) {
            return;
        }

        $orchestrator->executer(
            $exercice,
            $this->cheminFichier,
            $this->fichierNom,
            $this->tailleOctets,
            $this->hashSha256,
            $this->userId,
            $this->feuilles,
            $this->papaId,
            $this->dryRun,
        );
    }
}
