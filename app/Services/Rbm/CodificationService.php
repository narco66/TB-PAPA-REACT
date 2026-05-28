<?php

namespace App\Services\Rbm;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;

/**
 * Génération automatique des codes RBM/GAR conformes à la nomenclature CEEAC :
 *   Axe         : « AXE 1 »
 *   Produit     : « P.1.1 »
 *   Sous-Produit: « SP.1.1.1 »
 *   Activité    : « ACT.1.1.1.1 »
 *   Tâche       : « T.1.1.1.1.1 »
 *
 * Les segments correspondent à l'ordre hiérarchique au sein du parent.
 */
class CodificationService
{
    public function codeAxe(int $papaId, ?int $ordreOverride = null): string
    {
        $ordre = $ordreOverride ?? (Axe::where('papa_id', $papaId)->max('ordre') + 1);

        return "AXE {$ordre}";
    }

    public function codeProduit(Axe $axe, ?int $ordreOverride = null): string
    {
        $ordre = $ordreOverride ?? (Produit::where('axe_id', $axe->id)->max('ordre') + 1);

        return "P.{$axe->ordre}.{$ordre}";
    }

    public function codeSousProduit(SousProduit|Produit $parent, ?int $ordreOverride = null): string
    {
        $produit = $parent instanceof SousProduit ? $parent->produit : $parent;
        $produit->loadMissing('axe');
        $ordre = $ordreOverride ?? (SousProduit::where('produit_id', $produit->id)->max('ordre') + 1);

        return "SP.{$produit->axe->ordre}.{$produit->ordre}.{$ordre}";
    }

    public function codeActivite(SousProduit $sp, ?int $ordreOverride = null): string
    {
        $sp->loadMissing('produit.axe');
        $ordre = $ordreOverride ?? (Activite::where('sous_produit_id', $sp->id)->max('ordre') + 1);

        return "ACT.{$sp->produit->axe->ordre}.{$sp->produit->ordre}.{$sp->ordre}.{$ordre}";
    }

    public function codeTache(Activite $activite, ?int $ordreOverride = null): string
    {
        $activite->loadMissing('sousProduit.produit.axe');
        $ordre = $ordreOverride ?? (Tache::where('activite_id', $activite->id)->max('ordre') + 1);
        $sp = $activite->sousProduit;

        return "T.{$sp->produit->axe->ordre}.{$sp->produit->ordre}.{$sp->ordre}.{$activite->ordre}.{$ordre}";
    }

    /**
     * Recalcule tous les codes d'un PAPA (utile après réordonnancement).
     */
    public function recoderPapa(int $papaId): int
    {
        $count = 0;
        $axes = Axe::where('papa_id', $papaId)->orderBy('ordre')->orderBy('id')->get();
        foreach ($axes->values() as $iAxe => $axe) {
            $axe->ordre = $iAxe + 1;
            $axe->code = $this->codeAxe($papaId, $axe->ordre);
            $axe->saveQuietly();
            $count++;

            $produits = Produit::where('axe_id', $axe->id)->orderBy('ordre')->orderBy('id')->get();
            foreach ($produits->values() as $iProd => $produit) {
                $produit->ordre = $iProd + 1;
                $produit->code = $this->codeProduit($axe, $produit->ordre);
                $produit->saveQuietly();
                $count++;

                $sps = SousProduit::where('produit_id', $produit->id)->orderBy('ordre')->orderBy('id')->get();
                foreach ($sps->values() as $iSp => $sp) {
                    $sp->ordre = $iSp + 1;
                    $sp->setRelation('produit', $produit);
                    $sp->code = $this->codeSousProduit($sp, $sp->ordre);
                    $sp->saveQuietly();
                    $count++;

                    $activites = Activite::where('sous_produit_id', $sp->id)->orderBy('ordre')->orderBy('id')->get();
                    foreach ($activites->values() as $iAct => $act) {
                        $act->ordre = $iAct + 1;
                        $sp->setRelation('produit', $produit);
                        $produit->setRelation('axe', $axe);
                        $act->setRelation('sousProduit', $sp);
                        $act->code = $this->codeActivite($sp, $act->ordre);
                        $act->saveQuietly();
                        $count++;

                        $taches = Tache::where('activite_id', $act->id)->orderBy('ordre')->orderBy('id')->get();
                        foreach ($taches->values() as $iT => $tache) {
                            $tache->ordre = $iT + 1;
                            $act->setRelation('sousProduit', $sp);
                            $tache->setRelation('activite', $act);
                            $tache->code = $this->codeTache($act, $tache->ordre);
                            $tache->saveQuietly();
                            $count++;
                        }
                    }
                }
            }
        }

        return $count;
    }
}
