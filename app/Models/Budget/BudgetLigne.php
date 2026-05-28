<?php

namespace App\Models\Budget;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\Partenaire;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Ligne budgétaire CEEAC — nomenclature officielle :
 *   Titre → Chapitre → Article → Paragraphe → Code action/projet
 * Avec distinction CEEAC-EM (États Membres) vs PTF (Partenaires Techniques et Financiers).
 */
class BudgetLigne extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES_BUDGET = [
        'recette_interne', 'recette_externe',
        'fonctionnement', 'investissement', 'equipement',
        'dette', 'dotation', 'transfert', 'autre',
    ];

    public const NATURES = ['recette', 'depense'];

    protected $fillable = [
        'exercice_id', 'parent_id', 'niveau',
        'budget_chapitre_id', 'budget_article_id', 'budget_paragraphe_id',
        'titre_code', 'chapitre_code', 'article_code', 'paragraphe_code',
        'budget_ligne_code', 'code_action', 'code_projet',
        'libelle', 'description',
        'nature', 'type_budget', 'pilier',
        'montant_total', 'montant_ceeac_em', 'montant_ptf', 'devise',
        'budget_annee_precedente', 'realisation_annee_precedente',
        'taux_realisation_precedent', 'variation',
        'montant_engage', 'montant_liquide', 'montant_ordonnance', 'montant_paye',
        'montant_disponible', 'taux_consommation',
        'source_financement_id', 'partenaire_id',
        'axe_id', 'produit_id', 'sous_produit_id', 'activite_id', 'tache_id',
        'departement_id', 'direction_id',
        'statut', 'ordre', 'observations',
        'created_by', 'updated_by', 'validated_by', 'validated_at', 'version',
    ];

    protected $casts = [
        'montant_total' => 'float',
        'montant_ceeac_em' => 'float',
        'montant_ptf' => 'float',
        'budget_annee_precedente' => 'float',
        'realisation_annee_precedente' => 'float',
        'taux_realisation_precedent' => 'float',
        'variation' => 'float',
        'montant_engage' => 'float',
        'montant_liquide' => 'float',
        'montant_ordonnance' => 'float',
        'montant_paye' => 'float',
        'montant_disponible' => 'float',
        'taux_consommation' => 'float',
        'pilier' => 'integer',
        'niveau' => 'integer',
        'ordre' => 'integer',
        'validated_at' => 'datetime',
        'version' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'libelle', 'code_action', 'montant_total', 'montant_ceeac_em', 'montant_ptf',
                'statut', 'axe_id', 'produit_id', 'sous_produit_id', 'activite_id', 'tache_id',
            ])
            ->logOnlyDirty();
    }

    // === Relations ===

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(BudgetExercice::class, 'exercice_id');
    }

    public function budgetChapitre(): BelongsTo
    {
        return $this->belongsTo(BudgetChapitre::class, 'budget_chapitre_id');
    }

    public function budgetArticle(): BelongsTo
    {
        return $this->belongsTo(BudgetArticle::class, 'budget_article_id');
    }

    public function budgetParagraphe(): BelongsTo
    {
        return $this->belongsTo(BudgetParagraphe::class, 'budget_paragraphe_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(BudgetSourceFinancement::class, 'source_financement_id');
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function axe(): BelongsTo
    {
        return $this->belongsTo(Axe::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function sousProduit(): BelongsTo
    {
        return $this->belongsTo(SousProduit::class);
    }

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(BudgetMouvement::class, 'ligne_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // === Scopes ===

    public function scopeRacines(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    public function scopeRecettes(Builder $q): Builder
    {
        return $q->where('nature', 'recette');
    }

    public function scopeDepenses(Builder $q): Builder
    {
        return $q->where('nature', 'depense');
    }

    public function scopeCeeacEm(Builder $q): Builder
    {
        return $q->where('montant_ceeac_em', '>', 0);
    }

    public function scopePtf(Builder $q): Builder
    {
        return $q->where('montant_ptf', '>', 0);
    }

    // === Métier ===

    public function disponible(): float
    {
        return max(0, $this->montant_total - $this->montant_engage);
    }

    public function tauxConsommation(): float
    {
        if ($this->montant_total <= 0) {
            return 0.0;
        }

        return round(($this->montant_paye / $this->montant_total) * 100, 2);
    }

    public function tauxEngagement(): float
    {
        if ($this->montant_total <= 0) {
            return 0.0;
        }

        return round(($this->montant_engage / $this->montant_total) * 100, 2);
    }

    public function estEnDepassement(): bool
    {
        return $this->montant_engage > $this->montant_total;
    }

    /**
     * Recalcule les totaux à partir des enfants (consolidation bottom-up).
     */
    public function recalculerDepuisEnfants(): void
    {
        $enfants = $this->enfants;
        if ($enfants->isEmpty()) {
            return;
        }

        $this->montant_total = (float) $enfants->sum('montant_total');
        $this->montant_ceeac_em = (float) $enfants->sum('montant_ceeac_em');
        $this->montant_ptf = (float) $enfants->sum('montant_ptf');
        $this->montant_engage = (float) $enfants->sum('montant_engage');
        $this->montant_paye = (float) $enfants->sum('montant_paye');
        $this->montant_disponible = $this->disponible();
        $this->taux_consommation = $this->tauxConsommation();
        $this->saveQuietly();

        if ($this->parent_id) {
            $this->parent->recalculerDepuisEnfants();
        }
    }
}
