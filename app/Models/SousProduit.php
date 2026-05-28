<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Sous-Produit — niveau 3 de la chaîne RBM CEEAC.
 * Code : « SP.1.1.1 » (SP.{axe}.{produit}.{sp}).
 */
class SousProduit extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'sous_produits';

    protected $fillable = [
        'produit_id', 'code', 'libelle', 'description',
        'statut', 'ordre', 'poids', 'taux_execution',
        'date_debut', 'date_fin',
        'direction_id', 'responsable_id',
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

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class);
    }

    public function indicateurs(): HasMany
    {
        return $this->hasMany(Indicateur::class);
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
