<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Indicateur (KPI) — rattaché au niveau Sous-Produit dans la chaîne RBM CEEAC.
 *
 * Conforme au Cadre de Mesure du Rendement (CMR) RBM/GAR :
 * - Typologie CAD/OCDE : impact / effet / produit / processus
 * - Paliers trimestriels (T1-T4)
 * - Désagrégation (genre, géographique, vulnérabilité, âge)
 * - Méthodologie complète (collecte + validation séparés)
 * - Théorie du changement (hypothèses, risques)
 * - Polarité + seuils d'alerte
 * - Référentiels externes (ODD, Agenda 2063, etc.)
 */
class Indicateur extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = ['quantitatif', 'qualitatif'];

    public const CATEGORIES = ['impact', 'effet', 'produit', 'processus'];

    public const CATEGORIES_LABELS = [
        'impact' => 'Impact (changement à long terme)',
        'effet' => 'Effet / Outcome (changement à moyen terme)',
        'produit' => 'Produit / Output (livrable direct)',
        'processus' => 'Processus / Input (moyen mis en œuvre)',
    ];

    public const POLARITES = ['positive', 'negative', 'neutre'];

    public const POLARITES_LABELS = [
        'positive' => 'Plus c\'est élevé, mieux c\'est',
        'negative' => 'Plus c\'est bas, mieux c\'est',
        'neutre' => 'Respect d\'une cible exacte',
    ];

    public const FREQUENCES = ['mensuelle', 'trimestrielle', 'semestrielle', 'annuelle'];

    public const TRIMESTRES = ['T1', 'T2', 'T3', 'T4'];

    protected $fillable = [
        'sous_produit_id',
        'code', 'libelle', 'definition',
        'type', 'categorie', 'polarite', 'unite',
        'baseline', 'cible',
        'palier_t1', 'palier_t2', 'palier_t3', 'palier_t4',
        'seuil_alerte_bas', 'seuil_alerte_haut',
        'date_baseline',
        'methode_calcul', 'hypotheses', 'risques_associes',
        'frequence_collecte', 'source_donnees', 'instrument_collecte',
        'responsable_id', 'responsable_collecte_id',
        'desagregation_genre', 'desagregation_geographique',
        'desagregation_vulnerabilite', 'desagregation_age',
        'referentiels_externes',
        'valeur_actuelle', 'taux_realisation', 'tendance',
        'originated_from_import_id',
    ];

    protected $casts = [
        'baseline' => 'float',
        'cible' => 'float',
        'palier_t1' => 'float',
        'palier_t2' => 'float',
        'palier_t3' => 'float',
        'palier_t4' => 'float',
        'seuil_alerte_bas' => 'float',
        'seuil_alerte_haut' => 'float',
        'valeur_actuelle' => 'float',
        'taux_realisation' => 'float',
        'date_baseline' => 'date',
        'desagregation_genre' => 'boolean',
        'desagregation_geographique' => 'boolean',
        'desagregation_vulnerabilite' => 'boolean',
        'desagregation_age' => 'boolean',
        'referentiels_externes' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function sousProduit(): BelongsTo
    {
        return $this->belongsTo(SousProduit::class);
    }

    /** Responsable de validation (signature institutionnelle). */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /** Responsable de collecte (saisie terrain). */
    public function responsableCollecte(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_collecte_id');
    }

    public function valeurs(): HasMany
    {
        return $this->hasMany(ValeurIndicateur::class);
    }

    /**
     * Recalcul du taux de réalisation tenant compte de la polarité.
     * positive : (valeur - baseline) / (cible - baseline) × 100
     * negative : (baseline - valeur) / (baseline - cible) × 100
     * neutre   : 100 − |valeur - cible| / cible × 100
     */
    public function recalculerTauxRealisation(): void
    {
        if ($this->cible === null || $this->valeur_actuelle === null) {
            $this->taux_realisation = 0;

            return;
        }

        $baseline = (float) ($this->baseline ?? 0);
        $cible = (float) $this->cible;
        $valeur = (float) $this->valeur_actuelle;
        $polarite = $this->polarite ?? 'positive';

        $taux = match ($polarite) {
            'negative' => $this->tauxNegative($baseline, $cible, $valeur),
            'neutre' => $this->tauxNeutre($cible, $valeur),
            default => $this->tauxPositive($baseline, $cible, $valeur),
        };

        $this->taux_realisation = round(max(0, min(150, $taux)), 2);
    }

    /** Cible attendue pour le trimestre courant (T1..T4) compte tenu des paliers définis. */
    public function ciblePalier(string $trimestre): ?float
    {
        $champ = 'palier_' . strtolower($trimestre);

        return $this->{$champ} ?? null;
    }

    /** Renvoie l'écart par rapport au palier d'un trimestre donné, ou null si non défini. */
    public function ecartPalier(string $trimestre, float $valeur): ?float
    {
        $cible = $this->ciblePalier($trimestre);
        if ($cible === null) {
            return null;
        }

        return round($valeur - $cible, 4);
    }

    /** True si la valeur actuelle est hors de la plage d'alerte définie. */
    public function estHorsPlage(): bool
    {
        if ($this->valeur_actuelle === null) {
            return false;
        }
        if ($this->seuil_alerte_bas !== null && $this->valeur_actuelle < $this->seuil_alerte_bas) {
            return true;
        }
        if ($this->seuil_alerte_haut !== null && $this->valeur_actuelle > $this->seuil_alerte_haut) {
            return true;
        }

        return false;
    }

    /** Liste des dimensions de désagrégation activées. */
    public function dimensionsDesagregation(): array
    {
        $dimensions = [];
        if ($this->desagregation_genre) {
            $dimensions[] = 'Genre';
        }
        if ($this->desagregation_geographique) {
            $dimensions[] = 'Géographique';
        }
        if ($this->desagregation_vulnerabilite) {
            $dimensions[] = 'Vulnérabilité';
        }
        if ($this->desagregation_age) {
            $dimensions[] = 'Âge';
        }

        return $dimensions;
    }

    protected function tauxPositive(float $baseline, float $cible, float $valeur): float
    {
        $denominateur = $cible - $baseline;
        if (abs($denominateur) < 0.0001) {
            return $valeur >= $cible ? 100 : 0;
        }

        return (($valeur - $baseline) / $denominateur) * 100;
    }

    protected function tauxNegative(float $baseline, float $cible, float $valeur): float
    {
        $denominateur = $baseline - $cible;
        if (abs($denominateur) < 0.0001) {
            return $valeur <= $cible ? 100 : 0;
        }

        return (($baseline - $valeur) / $denominateur) * 100;
    }

    protected function tauxNeutre(float $cible, float $valeur): float
    {
        if (abs($cible) < 0.0001) {
            return 0;
        }

        return 100 - (abs($valeur - $cible) / abs($cible) * 100);
    }
}
