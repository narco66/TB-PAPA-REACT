<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Papa extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUT_BROUILLON = 'brouillon';

    public const STATUT_EN_VALIDATION = 'en_validation';

    public const STATUT_VALIDE = 'valide';

    public const STATUT_REVISE = 'revise';

    public const STATUT_CLOTURE = 'cloture';

    public const STATUT_ARCHIVE = 'archive';

    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_EN_VALIDATION,
        self::STATUT_VALIDE,
        self::STATUT_REVISE,
        self::STATUT_CLOTURE,
        self::STATUT_ARCHIVE,
    ];

    protected $fillable = [
        'annee',
        'version',
        'libelle',
        'description',
        'perimetre_institutionnel',
        'statut',
        'date_debut',
        'date_fin',
        'date_validation',
        'valide_par_id',
        'cloture_le',
        'cloture_par_id',
        'created_by',
        'verrouille',
    ];

    protected $casts = [
        'annee' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_validation' => 'datetime',
        'cloture_le' => 'datetime',
        'verrouille' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function clotureur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloture_par_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function axes(): HasMany
    {
        return $this->hasMany(Axe::class);
    }

    public function produits(): HasManyThrough
    {
        return $this->hasManyThrough(Produit::class, Axe::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(Validation::class, 'validable');
    }

    public function alertes(): MorphMany
    {
        return $this->morphMany(Alerte::class, 'alertable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // === Scopes ===

    public function scopeActif(Builder $q): Builder
    {
        return $q->whereIn('statut', [self::STATUT_VALIDE, self::STATUT_REVISE, self::STATUT_EN_VALIDATION]);
    }

    public function scopeAnnee(Builder $q, int $annee): Builder
    {
        return $q->where('annee', $annee);
    }

    // === Métier ===

    public function estVerrouille(): bool
    {
        return $this->verrouille || in_array($this->statut, [self::STATUT_CLOTURE, self::STATUT_ARCHIVE], true);
    }

    public function peutEtreModifie(): bool
    {
        return ! $this->estVerrouille() && in_array($this->statut, [self::STATUT_BROUILLON, self::STATUT_REVISE], true);
    }

    public function tauxExecutionPhysique(): float
    {
        $axes = $this->axes;
        if ($axes->isEmpty()) {
            return 0.0;
        }

        $totalPoids = (float) $axes->sum('poids');
        if ($totalPoids <= 0) {
            return round((float) $axes->avg('taux_execution'), 2);
        }

        $somme = $axes->sum(fn ($a) => (float) $a->taux_execution * (float) $a->poids);

        return round($somme / $totalPoids, 2);
    }

    public function tauxExecutionFinancier(): float
    {
        $budgetTotal = (float) $this->budgets()->sum('prevision');
        $consommation = (float) $this->budgets()->sum('consommation');
        if ($budgetTotal <= 0) {
            return 0.0;
        }

        return round(($consommation / $budgetTotal) * 100, 2);
    }
}
