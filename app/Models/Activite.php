<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Activité — niveau 4 de la chaîne RBM CEEAC.
 * Code : « ACT.1.1.1.1 ».
 */
class Activite extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'sous_produit_id', 'code', 'libelle', 'description',
        'statut', 'ordre', 'poids', 'taux_execution',
        'date_debut', 'date_fin', 'date_debut_reelle', 'date_fin_reelle',
        'niveau_risque', 'est_jalon',
        'direction_id', 'responsable_id', 'point_focal_id',
        'created_by', 'updated_by',
        'originated_from_import_id',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_debut_reelle' => 'date',
        'date_fin_reelle' => 'date',
        'poids' => 'float',
        'taux_execution' => 'float',
        'est_jalon' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function sousProduit(): BelongsTo
    {
        return $this->belongsTo(SousProduit::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function pointFocal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'point_focal_id');
    }

    public function taches(): HasMany
    {
        return $this->hasMany(Tache::class);
    }

    public function dependances(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'activite_dependances',
            'activite_id',
            'depend_de_id',
        )->withPivot(['type', 'decalage_jours'])->withTimestamps();
    }

    public function budgets(): MorphMany
    {
        return $this->morphMany(Budget::class, 'budgetable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function alertes(): MorphMany
    {
        return $this->morphMany(Alerte::class, 'alertable');
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(Validation::class, 'validable');
    }

    public function estEnRetard(): bool
    {
        if (in_array($this->statut, ['realisee', 'annulee', 'archive'], true)) {
            return false;
        }

        return $this->date_fin?->isPast() && $this->taux_execution < 100;
    }
}
