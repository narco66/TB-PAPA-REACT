<?php

namespace App\Services\Workflow;

use App\Models\Papa;
use App\Models\User;
use App\Models\Validation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Moteur de workflow institutionnel pour TB-PAPA-CEEAC.
 *
 * Gère la machine à états des ressources soumises à validation hiérarchique
 * (PAPA, Axes, Activités à terme) en enregistrant chaque transition dans la
 * table `validations` (audit trail + visa électronique).
 */
class WorkflowService
{
    /**
     * Matrice des transitions autorisées par type de ressource.
     * Format : [from_statut => [transition_key => [to_statut, etape_validation, permission_requise]]].
     */
    public const MATRICE_PAPA = [
        Papa::STATUT_BROUILLON => [
            'submit' => [
                'to' => Papa::STATUT_EN_VALIDATION,
                'etape' => 'soumission',
                'permission' => 'papa.submit',
                'libelle' => 'Soumettre pour validation',
            ],
        ],
        Papa::STATUT_EN_VALIDATION => [
            'approve' => [
                'to' => Papa::STATUT_VALIDE,
                'etape' => 'validation_presidence',
                'permission' => 'papa.validate',
                'libelle' => 'Approuver',
            ],
            'reject' => [
                'to' => Papa::STATUT_BROUILLON,
                'etape' => 'validation_presidence',
                'permission' => 'papa.validate',
                'libelle' => 'Rejeter (retour brouillon)',
                'decision' => 'rejete',
            ],
        ],
        Papa::STATUT_VALIDE => [
            'revise' => [
                'to' => Papa::STATUT_REVISE,
                'etape' => 'revue_technique',
                'permission' => 'papa.revise',
                'libelle' => 'Mettre en révision',
            ],
            'close' => [
                'to' => Papa::STATUT_CLOTURE,
                'etape' => 'cloture',
                'permission' => 'papa.close',
                'libelle' => 'Clôturer',
            ],
        ],
        Papa::STATUT_REVISE => [
            'resubmit' => [
                'to' => Papa::STATUT_EN_VALIDATION,
                'etape' => 'soumission',
                'permission' => 'papa.submit',
                'libelle' => 'Re-soumettre après révision',
            ],
        ],
        Papa::STATUT_CLOTURE => [
            'archive' => [
                'to' => Papa::STATUT_ARCHIVE,
                'etape' => 'cloture',
                'permission' => 'papa.archive',
                'libelle' => 'Archiver',
            ],
        ],
    ];

    /**
     * Exécute une transition. Lance une exception si interdite ou non autorisée.
     */
    public function transition(Model $entite, User $user, string $key, ?string $commentaire = null): Validation
    {
        $matrice = $this->matricePour($entite);
        $statutCourant = (string) $entite->statut;

        if (! isset($matrice[$statutCourant][$key])) {
            throw new InvalidArgumentException("Transition « {$key} » non autorisée depuis l'état « {$statutCourant} ».");
        }

        $config = $matrice[$statutCourant][$key];

        if (! $user->can($config['permission'])) {
            throw new RuntimeException("Permission « {$config['permission']} » requise pour cette transition.");
        }

        return DB::transaction(function () use ($entite, $user, $config, $commentaire, $statutCourant) {
            $avant = ['statut' => $statutCourant];
            $apres = ['statut' => $config['to']];

            $champsCloture = $config['to'] === Papa::STATUT_CLOTURE ? [
                'cloture_le' => now(),
                'cloture_par_id' => $user->id,
                'verrouille' => true,
            ] : [];

            $champsValidation = $config['to'] === Papa::STATUT_VALIDE ? [
                'date_validation' => now(),
                'valide_par_id' => $user->id,
            ] : [];

            $champsArchive = $config['to'] === Papa::STATUT_ARCHIVE ? [
                'verrouille' => true,
            ] : [];

            $entite->forceFill([
                'statut' => $config['to'],
                ...$champsCloture,
                ...$champsValidation,
                ...$champsArchive,
            ])->save();

            return Validation::create([
                'validable_type' => $entite->getMorphClass(),
                'validable_id' => $entite->id,
                'etape' => $config['etape'],
                'decision' => $config['decision'] ?? 'approuve',
                'demandeur_id' => $user->id,
                'valideur_id' => $user->id,
                'decide_at' => now(),
                'commentaire' => $commentaire,
                'donnees_avant' => $avant,
                'donnees_apres' => $apres,
            ]);
        });
    }

    /**
     * Renvoie les transitions disponibles pour un utilisateur donné sur une entité.
     *
     * @return array<int, array{key:string,libelle:string,to:string,permission_ok:bool}>
     */
    public function transitionsDisponibles(Model $entite, User $user): array
    {
        $matrice = $this->matricePour($entite);
        $statut = (string) $entite->statut;

        if (! isset($matrice[$statut])) {
            return [];
        }

        $resultat = [];
        foreach ($matrice[$statut] as $key => $config) {
            $resultat[] = [
                'key' => $key,
                'libelle' => $config['libelle'],
                'to' => $config['to'],
                'permission_ok' => $user->can($config['permission']),
                'permission' => $config['permission'],
            ];
        }

        return $resultat;
    }

    /**
     * Historique chronologique des validations d'une entité.
     */
    public function historique(Model $entite): Collection
    {
        return Validation::with(['demandeur:id,name,fonction', 'valideur:id,name,fonction'])
            ->where('validable_type', $entite->getMorphClass())
            ->where('validable_id', $entite->id)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Sélectionne la matrice de transitions pour le type d'entité passé.
     *
     * @return array<string, array<string, array{to:string,etape:string,permission:string,libelle:string,decision?:string}>>
     */
    protected function matricePour(Model $entite): array
    {
        return match ($entite::class) {
            Papa::class => self::MATRICE_PAPA,
            default => throw new InvalidArgumentException('Aucune matrice de workflow définie pour ' . $entite::class),
        };
    }
}
