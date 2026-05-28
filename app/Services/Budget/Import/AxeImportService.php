<?php

namespace App\Services\Budget\Import;

use App\Models\Axe;
use App\Models\Departement;
use App\Models\User;
use App\Services\Budget\ExcelAnalyzerService;
use Illuminate\Database\Eloquent\Model;

class AxeImportService extends AbstractEntityImportService
{
    public function typeDonnees(): string
    {
        return 'axes';
    }

    protected function colonnesObligatoires(): array
    {
        return ['libelle'];
    }

    protected function synonymes(): array
    {
        return ExcelAnalyzerService::SYNONYMS['axes'] ?? [];
    }

    protected function importerLigne(array $ligne, int $numeroLigne, array $contexte): ?Model
    {
        $papaId = $contexte['papa_id'] ?? null;
        if (! $papaId) {
            return null;
        }

        $departement = isset($ligne['departement']) && $ligne['departement']
            ? Departement::where('code', $ligne['departement'])
                ->orWhere('libelle', $ligne['departement'])
                ->first()
            : null;

        $responsable = isset($ligne['responsable']) && $ligne['responsable']
            ? User::where('name', $ligne['responsable'])
                ->orWhere('email', $ligne['responsable'])
                ->first()
            : null;

        $code = $ligne['code'] ?? null;

        // Upsert : si un code existe déjà sur ce papa, on met à jour ; sinon le code sera attribué par l'observer.
        $axe = $code
            ? Axe::where('papa_id', $papaId)->where('code', $code)->first()
            : null;

        if ($axe) {
            $axe->update([
                'libelle' => $ligne['libelle'],
                'description' => $ligne['description'] ?? $axe->description,
                'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : $axe->poids,
                'departement_id' => $departement?->id ?? $axe->departement_id,
                'responsable_id' => $responsable?->id ?? $axe->responsable_id,
            ]);
        } else {
            $axe = Axe::create([
                'papa_id' => $papaId,
                'libelle' => $ligne['libelle'],
                'description' => $ligne['description'] ?? null,
                'poids' => isset($ligne['poids']) ? (float) $ligne['poids'] : 0,
                'departement_id' => $departement?->id,
                'responsable_id' => $responsable?->id,
                'statut' => 'brouillon',
                'originated_from_import_id' => $contexte['parent_import_id'] ?? null,
            ]);
        }

        return $axe;
    }
}
