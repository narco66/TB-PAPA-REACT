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
 * Produit — niveau 2 de la chaîne RBM CEEAC.
 * Code : « P.1.1 » (P.{ordre_axe}.{ordre_produit}).
 */
class Produit extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'axe_id', 'code', 'libelle', 'description',
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

    public function axe(): BelongsTo
    {
        return $this->belongsTo(Axe::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function sousProduits(): HasMany
    {
        return $this->hasMany(SousProduit::class);
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
