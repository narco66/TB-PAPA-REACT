<?php

namespace App\Services\Budget\Import;

use App\Models\Budget\BudgetImport;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Services\Budget\ExcelAnalyzerService;
use Illuminate\Database\Eloquent\Model;

class SousProduitImportService extends AbstractEntityImportService
{
    public function typeDonnees(): string
    {
        return 'sous_produits';
    }

    protected function colonnesObligatoires(): array
    {
        return ['code_produit', 'libelle'];
    }

    protected function synonymes(): array
    {
        return ExcelAnalyzerService::SYNONYMS['sous_produits'] ?? [];
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

        $produit = Produit::whereHas('axe', fn ($q) => $q->where('papa_id', $papaId))
            ->where('code', $ligne['code_produit'])
            ->first();

        if (! $produit) {
            $this->ajouterErreur(
                $import, $nomFeuille, $numeroLigne, 'code_produit', $ligne['code_produit'],
                'erreur', 'reference_inexistante',
                "Produit « {$ligne['code_produit']} » introuvable.",
                'Importez les axes et produits en premier (ordre : Axes → Produits → Sous-Produits).',
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

        $produit = Produit::whereHas('axe', fn ($q) => $q->where('papa_id', $papaId))
            ->where('code', $ligne['code_produit'])
            ->first();

        if (! $produit) {
            return null;
        }

        $code = $ligne['code'] ?? null;
        $sp = $code
            ? SousProduit::where('produit_id', $produit->id)->where('code', $code)->first()
            : null;

        if ($sp) {
            $sp->update([
                'libelle' => $ligne['libelle'],
                'description' => $ligne['description'] ?? $sp->description,
                'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : $sp->poids,
            ]);
        } else {
            $sp = SousProduit::create([
                'produit_id' => $produit->id,
                'libelle' => $ligne['libelle'],
                'description' => $ligne['description'] ?? null,
                'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : 0,
                'statut' => 'brouillon',
                'originated_from_import_id' => $contexte['parent_import_id'] ?? null,
            ]);
        }

        return $sp;
    }
}
