<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Axe stratégique — niveau 1 de la chaîne RBM/GAR CEEAC.
 * Code : « AXE 1 », « AXE 2 », etc. (généré automatiquement).
 */
class Axe extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUTS = ['brouillon', 'soumis', 'en_validation', 'valide', 'rejete', 'archive'];

    protected $fillable = [
        'papa_id', 'code', 'libelle', 'description',
        'statut', 'ordre', 'poids', 'taux_execution',
        'date_debut', 'date_fin',
        'departement_id', 'responsable_id',
        'created_by', 'updated_by',
        'originated_from_import_id',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'poids' => 'float',
        'taux_execution' => 'float',
        'ordre' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function papa(): BelongsTo
    {
        return $this->belongsTo(Papa::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class);
    }

    public function sousProduits(): HasManyThrough
    {
        return $this->hasManyThrough(SousProduit::class, Produit::class);
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
}
