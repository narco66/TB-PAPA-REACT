<?php

namespace App\Observers;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Services\Rbm\CodificationService;
use App\Services\Rbm\RecalculAvancementService;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer générique attaché aux 5 modèles RBM (Axe, Produit, SousProduit, Activité, Tâche).
 * - Sur creating : attribue l'ordre suivant + le code automatique si absent.
 * - Sur updated/deleted : déclenche la propagation bottom-up du taux d'exécution.
 * - Sur creating : positionne created_by / updated_by depuis auth().
 */
class RbmCodificationObserver
{
    public function __construct(
        protected CodificationService $codification,
        protected RecalculAvancementService $recalcul,
    ) {}

    public function creating(Model $model): void
    {
        // Audit utilisateur
        if (auth()->check()) {
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
            if (empty($model->updated_by)) {
                $model->updated_by = auth()->id();
            }
        }

        // Ordre (si absent)
        if (empty($model->ordre)) {
            $model->ordre = $this->ordreSuivant($model);
        }

        // Code (si absent)
        if (empty($model->code)) {
            $model->code = $this->genererCode($model);
        }
    }

    public function updating(Model $model): void
    {
        if (auth()->check()) {
            $model->updated_by = auth()->id();
        }
    }

    public function updated(Model $model): void
    {
        $this->propagerRecalcul($model);
    }

    public function deleted(Model $model): void
    {
        $this->propagerRecalcul($model);
    }

    // === Helpers ===

    protected function ordreSuivant(Model $model): int
    {
        return match (true) {
            $model instanceof Axe => Axe::where('papa_id', $model->papa_id)->max('ordre') + 1,
            $model instanceof Produit => Produit::where('axe_id', $model->axe_id)->max('ordre') + 1,
            $model instanceof SousProduit => SousProduit::where('produit_id', $model->produit_id)->max('ordre') + 1,
            $model instanceof Activite => Activite::where('sous_produit_id', $model->sous_produit_id)->max('ordre') + 1,
            $model instanceof Tache => Tache::where('activite_id', $model->activite_id)->max('ordre') + 1,
            default => 1,
        };
    }

    protected function genererCode(Model $model): string
    {
        return match (true) {
            $model instanceof Axe => $this->codification->codeAxe($model->papa_id, $model->ordre),
            $model instanceof Produit => $this->codification->codeProduit(
                Axe::find($model->axe_id),
                $model->ordre,
            ),
            $model instanceof SousProduit => $this->codification->codeSousProduit(
                Produit::with('axe')->find($model->produit_id),
                $model->ordre,
            ),
            $model instanceof Activite => $this->codification->codeActivite(
                SousProduit::with('produit.axe')->find($model->sous_produit_id),
                $model->ordre,
            ),
            $model instanceof Tache => $this->codification->codeTache(
                Activite::with('sousProduit.produit.axe')->find($model->activite_id),
                $model->ordre,
            ),
            default => '',
        };
    }

    protected function propagerRecalcul(Model $model): void
    {
        // On ne propage que si le taux_execution ou la suppression peuvent affecter le parent
        match (true) {
            $model instanceof Tache => $this->recalcul->depuisTache($model),
            $model instanceof Activite => $this->recalcul->depuisActivite($model),
            $model instanceof SousProduit => $this->recalcul->depuisSousProduit($model),
            $model instanceof Produit => $this->recalcul->depuisProduit($model),
            $model instanceof Axe => $this->recalcul->depuisAxe($model),
            default => null,
        };
    }
}
