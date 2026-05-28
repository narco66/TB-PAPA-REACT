<?php

namespace App\Services\Budget\Import;

use App\Models\Axe;
use App\Models\Budget\BudgetImport;
use App\Models\Produit;
use App\Services\Budget\ExcelAnalyzerService;
use Illuminate\Database\Eloquent\Model;

class ProduitImportService extends AbstractEntityImportService
{
    public function typeDonnees(): string
    {
        return 'produits';
    }

    protected function colonnesObligatoires(): array
    {
        return ['code_axe', 'libelle'];
    }

    protected function synonymes(): array
    {
        return ExcelAnalyzerService::SYNONYMS['produits'] ?? [];
    }

    protected function validerLigne(BudgetImport $import, array $ligne, int $numeroLigne, string $nomFeuille): bool
    {
        if (! parent::validerLigne($import, $ligne, $numeroLigne, $nomFeuille)) {
            return false;
        }

        $papaId = $import->parent?->options['papa_id'] ?? null;
        if (! $papaId) {
            return true; // Validation différée à importerLigne
        }

        $axe = Axe::where('papa_id', $papaId)
            ->where('code', $ligne['code_axe'])
            ->first();

        if (! $axe) {
            $this->ajouterErreur(
                $import, $nomFeuille, $numeroLigne, 'code_axe', $ligne['code_axe'],
                'erreur', 'reference_inexistante',
                "Axe « {$ligne['code_axe']} » introuvable pour ce PAPA.",
                "Vérifiez que l'axe a bien été importé avant les produits (ordre : Axes → Produits).",
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

        $axe = Axe::where('papa_id', $papaId)
            ->where('code', $ligne['code_axe'])
            ->first();

        if (! $axe) {
            return null; // Déjà signalé par validerLigne
        }

        $code = $ligne['code'] ?? null;

        $produit = $code
            ? Produit::where('axe_id', $axe->id)->where('code', $code)->first()
            : null;

        if ($produit) {
            $produit->update([
                'libelle' => $ligne['libelle'],
                'description' => $ligne['description'] ?? $produit->description,
                'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : $produit->poids,
            ]);
        } else {
            $produit = Produit::create([
                'axe_id' => $axe->id,
                'libelle' => $ligne['libelle'],
                'description' => $ligne['description'] ?? null,
                'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : 0,
                'statut' => 'brouillon',
                'originated_from_import_id' => $contexte['parent_import_id'] ?? null,
            ]);
        }

        return $produit;
    }
}
