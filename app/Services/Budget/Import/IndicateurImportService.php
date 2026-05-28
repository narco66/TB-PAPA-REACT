<?php

namespace App\Services\Budget\Import;

use App\Models\Budget\BudgetImport;
use App\Models\Indicateur;
use App\Models\SousProduit;
use App\Services\Budget\ExcelAnalyzerService;
use Illuminate\Database\Eloquent\Model;

class IndicateurImportService extends AbstractEntityImportService
{
    public function typeDonnees(): string
    {
        return 'indicateurs';
    }

    protected function colonnesObligatoires(): array
    {
        return ['code_sous_produit', 'code', 'libelle', 'type', 'categorie', 'frequence_collecte'];
    }

    protected function synonymes(): array
    {
        return ExcelAnalyzerService::SYNONYMS['indicateurs'] ?? [];
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

        $sp = SousProduit::whereHas('produit.axe', fn ($q) => $q->where('papa_id', $papaId))
            ->where('code', $ligne['code_sous_produit'])
            ->first();

        if (! $sp) {
            $this->ajouterErreur(
                $import, $nomFeuille, $numeroLigne, 'code_sous_produit', $ligne['code_sous_produit'],
                'erreur', 'reference_inexistante',
                "Sous-Produit « {$ligne['code_sous_produit']} » introuvable.",
                'Importez les sous-produits avant les indicateurs.',
            );

            return false;
        }

        if (! in_array($ligne['type'], Indicateur::TYPES, true)) {
            $this->ajouterErreur(
                $import, $nomFeuille, $numeroLigne, 'type', $ligne['type'],
                'erreur', 'format_invalide',
                "Type invalide : « {$ligne['type'] }».",
                'Valeurs autorisées : ' . implode(', ', Indicateur::TYPES),
            );

            return false;
        }

        if (! in_array($ligne['categorie'], Indicateur::CATEGORIES, true)) {
            $this->ajouterErreur(
                $import, $nomFeuille, $numeroLigne, 'categorie', $ligne['categorie'],
                'erreur', 'format_invalide',
                "Catégorie invalide : « {$ligne['categorie']} ».",
                'Valeurs autorisées : ' . implode(', ', Indicateur::CATEGORIES),
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

        $sp = SousProduit::whereHas('produit.axe', fn ($q) => $q->where('papa_id', $papaId))
            ->where('code', $ligne['code_sous_produit'])
            ->first();

        if (! $sp) {
            return null;
        }

        $indicateur = Indicateur::where('sous_produit_id', $sp->id)
            ->where('code', $ligne['code'])
            ->first();

        $champs = [
            'sous_produit_id' => $sp->id,
            'code' => $ligne['code'],
            'libelle' => $ligne['libelle'],
            'definition' => $ligne['definition'] ?? null,
            'type' => $ligne['type'],
            'categorie' => $ligne['categorie'],
            'unite' => $ligne['unite'] ?? null,
            'baseline' => isset($ligne['baseline']) ? (float) $ligne['baseline'] : null,
            'cible' => isset($ligne['cible']) ? (float) $ligne['cible'] : null,
            'frequence_collecte' => $ligne['frequence_collecte'],
        ];

        if ($indicateur) {
            $indicateur->update($champs);
        } else {
            $indicateur = Indicateur::create([
                ...$champs,
                'originated_from_import_id' => $contexte['parent_import_id'] ?? null,
            ]);
        }

        return $indicateur;
    }
}
