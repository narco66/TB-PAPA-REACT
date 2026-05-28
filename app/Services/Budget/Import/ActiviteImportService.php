<?php

namespace App\Services\Budget\Import;

use App\Models\Activite;
use App\Models\Budget\BudgetImport;
use App\Models\Direction;
use App\Models\SousProduit;
use App\Models\User;
use App\Services\Budget\ExcelAnalyzerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ActiviteImportService extends AbstractEntityImportService
{
    public function typeDonnees(): string
    {
        return 'activites';
    }

    protected function colonnesObligatoires(): array
    {
        return ['code_sous_produit', 'libelle', 'date_debut', 'date_fin'];
    }

    protected function synonymes(): array
    {
        return ExcelAnalyzerService::SYNONYMS['activites'] ?? [];
    }

    protected function validerLigne(BudgetImport $import, array $ligne, int $numeroLigne, string $nomFeuille): bool
    {
        if (! parent::validerLigne($import, $ligne, $numeroLigne, $nomFeuille)) {
            return false;
        }

        $papaId = $import->parent?->options['papa_id'] ?? null;
        if ($papaId) {
            $sp = SousProduit::whereHas('produit.axe', fn ($q) => $q->where('papa_id', $papaId))
                ->where('code', $ligne['code_sous_produit'])
                ->first();

            if (! $sp) {
                $this->ajouterErreur(
                    $import, $nomFeuille, $numeroLigne, 'code_sous_produit', $ligne['code_sous_produit'],
                    'erreur', 'reference_inexistante',
                    "Sous-Produit « {$ligne['code_sous_produit']} » introuvable.",
                    'Importez les sous-produits avant les activités.',
                );

                return false;
            }
        }

        $debut = $this->parseDate($ligne['date_debut'] ?? null);
        $fin = $this->parseDate($ligne['date_fin'] ?? null);
        if ($debut && $fin && $fin->lt($debut)) {
            $this->ajouterErreur(
                $import, $nomFeuille, $numeroLigne, 'date_fin', $ligne['date_fin'],
                'erreur', 'format_invalide',
                'La date de fin est antérieure à la date de début.',
                'Vérifiez les colonnes date_debut et date_fin.',
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

        $direction = isset($ligne['departement']) && $ligne['departement']
            ? Direction::where('code', $ligne['departement'])->orWhere('libelle', $ligne['departement'])->first()
            : null;

        $responsable = $this->resoudreUser($ligne['responsable'] ?? null);
        $pointFocal = $this->resoudreUser($ligne['point_focal'] ?? null);

        $code = $ligne['code'] ?? null;
        $activite = $code
            ? Activite::where('sous_produit_id', $sp->id)->where('code', $code)->first()
            : null;

        $champs = [
            'sous_produit_id' => $sp->id,
            'libelle' => $ligne['libelle'],
            'description' => $ligne['description'] ?? null,
            'date_debut' => $this->parseDate($ligne['date_debut'] ?? null),
            'date_fin' => $this->parseDate($ligne['date_fin'] ?? null),
            'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : 0,
            'direction_id' => $direction?->id,
            'responsable_id' => $responsable?->id,
            'point_focal_id' => $pointFocal?->id,
        ];

        if ($activite) {
            $activite->update($champs);
        } else {
            $activite = Activite::create([
                ...$champs,
                'statut' => 'planifiee',
                'originated_from_import_id' => $contexte['parent_import_id'] ?? null,
            ]);
        }

        return $activite;
    }

    protected function parseDate(mixed $valeur): ?Carbon
    {
        if (! $valeur) {
            return null;
        }

        try {
            if (is_numeric($valeur)) {
                // Excel date serial (depuis 1900-01-01)
                return Carbon::create(1899, 12, 30)->addDays((int) $valeur);
            }

            return Carbon::parse((string) $valeur);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resoudreUser(?string $nomOuEmail): ?User
    {
        if (! $nomOuEmail) {
            return null;
        }

        return User::where('name', $nomOuEmail)
            ->orWhere('email', $nomOuEmail)
            ->first();
    }
}
