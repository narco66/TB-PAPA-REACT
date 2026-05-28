<?php

namespace App\Services\Budget\Import;

use App\Models\Activite;
use App\Models\Budget\BudgetImport;
use App\Models\Tache;
use App\Services\Budget\ExcelAnalyzerService;
use Illuminate\Database\Eloquent\Model;

class TacheImportService extends AbstractEntityImportService
{
    public function typeDonnees(): string
    {
        return 'taches';
    }

    protected function colonnesObligatoires(): array
    {
        return ['code_activite', 'libelle'];
    }

    protected function synonymes(): array
    {
        return ExcelAnalyzerService::SYNONYMS['taches'] ?? [];
    }

    protected function validerLigne(BudgetImport $import, array $ligne, int $numeroLigne, string $nomFeuille): bool
    {
        if (! parent::validerLigne($import, $ligne, $numeroLigne, $nomFeuille)) {
            return false;
        }

        $papaId = $import->parent?->options['papa_id'] ?? null;
        if (! $papaId) {
            return true;
        }

        $activite = Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))
            ->where('code', $ligne['code_activite'])
            ->first();

        if (! $activite) {
            $this->ajouterErreur(
                $import, $nomFeuille, $numeroLigne, 'code_activite', $ligne['code_activite'],
                'erreur', 'reference_inexistante',
                "Activité « {$ligne['code_activite']} » introuvable.",
                'Importez les activités avant les tâches.',
            );

            return false;
        }

        return true;
    }

    protected function importerLigne(array $ligne, int $numeroLigne, array $contexte): ?Model
    {
        $papaId = $contexte['papa_id'] ?? null;
        if (! $papaId) {
            return null;
        }

        $activite = Activite::whereHas('sousProduit.produit.axe', fn ($q) => $q->where('papa_id', $papaId))
            ->where('code', $ligne['code_activite'])
            ->first();

        if (! $activite) {
            return null;
        }

        $code = $ligne['code'] ?? null;
        $tache = $code
            ? Tache::where('activite_id', $activite->id)->where('code', $code)->first()
            : null;

        $champs = [
            'activite_id' => $activite->id,
            'libelle' => $ligne['libelle'],
            'description' => $ligne['description'] ?? null,
            'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : 0,
            'taux_execution' => isset($ligne['taux_execution']) ? min(100, max(0, (float) $ligne['taux_execution'])) : 0,
        ];

        if ($tache) {
            $tache->update($champs);
        } else {
            $tache = Tache::create([
                ...$champs,
                'statut' => 'planifiee',
                'originated_from_import_id' => $contexte['parent_import_id'] ?? null,
            ]);
        }

        return $tache;
    }
}
