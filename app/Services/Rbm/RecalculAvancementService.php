<?php

namespace App\Services\Rbm;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;

/**
 * Propagation bottom-up du taux d'exécution dans la chaîne RBM/GAR CEEAC :
 *   Tâche → Activité → Sous-Produit → Produit → Axe.
 *
 * Le calcul à chaque niveau est la moyenne pondérée par le poids des enfants.
 * Si aucun enfant n'a de poids défini (>0), la moyenne arithmétique est utilisée.
 */
class RecalculAvancementService
{
    public function depuisTache(Tache $tache): void
    {
        $this->recalculerActivite($tache->activite_id);
    }

    public function depuisActivite(Activite $activite): void
    {
        $this->recalculerActivite($activite->id);
    }

    public function depuisSousProduit(SousProduit $sp): void
    {
        $this->recalculerSousProduit($sp->id);
    }

    public function depuisProduit(Produit $produit): void
    {
        $this->recalculerProduit($produit->id);
    }

    public function depuisAxe(Axe $axe): void
    {
        $this->recalculerAxe($axe->id);
    }

    // === Propagations ===

    protected function recalculerActivite(int $activiteId): void
    {
        $activite = Activite::find($activiteId);
        if (! $activite) {
            return;
        }

        $taches = Tache::where('activite_id', $activiteId)->get();
        if ($taches->isNotEmpty()) {
            $activite->taux_execution = $this->moyennePonderee($taches);
            $activite->saveQuietly();
        }

        $this->recalculerSousProduit($activite->sous_produit_id);
    }

    protected function recalculerSousProduit(int $sousProduitId): void
    {
        $sp = SousProduit::find($sousProduitId);
        if (! $sp) {
            return;
        }

        $activites = Activite::where('sous_produit_id', $sousProduitId)->get();
        if ($activites->isNotEmpty()) {
            $sp->taux_execution = $this->moyennePonderee($activites);
            $sp->saveQuietly();
        }

        $this->recalculerProduit($sp->produit_id);
    }

    protected function recalculerProduit(int $produitId): void
    {
        $produit = Produit::find($produitId);
        if (! $produit) {
            return;
        }

        $sps = SousProduit::where('produit_id', $produitId)->get();
        if ($sps->isNotEmpty()) {
            $produit->taux_execution = $this->moyennePonderee($sps);
            $produit->saveQuietly();
        }

        $this->recalculerAxe($produit->axe_id);
    }

    protected function recalculerAxe(int $axeId): void
    {
        $axe = Axe::find($axeId);
        if (! $axe) {
            return;
        }

        $produits = Produit::where('axe_id', $axeId)->get();
        if ($produits->isNotEmpty()) {
            $axe->taux_execution = $this->moyennePonderee($produits);
            $axe->saveQuietly();
        }
    }

    /**
     * Moyenne pondérée par le champ `poids`.
     * Fallback : moyenne arithmétique si tous les poids sont nuls.
     */
    protected function moyennePonderee($collection): float
    {
        $totalPoids = (float) $collection->sum('poids');
        if ($totalPoids <= 0) {
            return round((float) $collection->avg('taux_execution'), 2);
        }
        $somme = $collection->sum(fn ($e) => (float) $e->taux_execution * (float) $e->poids);

        return round($somme / $totalPoids, 2);
    }

    /**
     * Recalcul global pour un PAPA donné (parcours complet).
     */
    public function recalculerPapa(int $papaId): array
    {
        $stats = ['axes' => 0, 'produits' => 0, 'sous_produits' => 0, 'activites' => 0];

        Axe::where('papa_id', $papaId)->get()->each(function (Axe $axe) use (&$stats) {
            Produit::where('axe_id', $axe->id)->get()->each(function (Produit $p) use (&$stats) {
                SousProduit::where('produit_id', $p->id)->get()->each(function (SousProduit $sp) use (&$stats) {
                    Activite::where('sous_produit_id', $sp->id)->get()->each(function (Activite $act) use (&$stats) {
                        $taches = Tache::where('activite_id', $act->id)->get();
                        if ($taches->isNotEmpty()) {
                            $act->taux_execution = $this->moyennePonderee($taches);
                            $act->saveQuietly();
                        }
                        $stats['activites']++;
                    });
                    $activites = Activite::where('sous_produit_id', $sp->id)->get();
                    if ($activites->isNotEmpty()) {
                        $sp->taux_execution = $this->moyennePonderee($activites);
                        $sp->saveQuietly();
                    }
                    $stats['sous_produits']++;
                });
                $sps = SousProduit::where('produit_id', $p->id)->get();
                if ($sps->isNotEmpty()) {
                    $p->taux_execution = $this->moyennePonderee($sps);
                    $p->saveQuietly();
                }
                $stats['produits']++;
            });
            $produits = Produit::where('axe_id', $axe->id)->get();
            if ($produits->isNotEmpty()) {
                $axe->taux_execution = $this->moyennePonderee($produits);
                $axe->saveQuietly();
            }
            $stats['axes']++;
        });

        return $stats;
    }
}
