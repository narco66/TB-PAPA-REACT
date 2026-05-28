<?php

namespace App\Console\Commands;

use App\Services\AlerteService;
use Illuminate\Console\Command;

class DetecterAlertesCommand extends Command
{
    protected $signature = 'tbpapa:detecter-alertes';

    protected $description = 'Détecte automatiquement les retards, dérives budgétaires et sous-performances (Section 6.10 du CDC).';

    public function handle(AlerteService $service): int
    {
        $this->info('Détection des alertes automatiques en cours...');
        $stats = $service->detecterAlertes();

        $this->table(
            ['Catégorie', 'Nouvelles alertes'],
            [
                ['Retards', $stats['retards']],
                ['Dérives budgétaires', $stats['derives_budgetaires']],
                ['Sous-performances', $stats['sous_performances']],
            ],
        );

        $total = array_sum($stats);
        $this->info("Total : {$total} alerte(s) générée(s).");

        return self::SUCCESS;
    }
}
