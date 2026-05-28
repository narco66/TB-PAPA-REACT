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
 * Tâche — niveau 5 de la chaîne RBM CEEAC.
 * Code : « T.1.1.1.1.1 ».
 */
class Tache extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'activite_id', 'tache_parente_id',
        'code', 'libelle', 'description',
        'statut', 'ordre', 'poids', 'taux_execution',
        'date_debut', 'date_fin',
        'responsable_id', 'assigne_a_id',
        'created_by', 'updated_by',
        'originated_from_import_id',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'poids' => 'float',
        'taux_execution' => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    public function parente(): BelongsTo
    {
        return $this->belongsTo(self::class, 'tache_parente_id');
    }

    public function sousTaches(): HasMany
    {
        return $this->hasMany(self::class, 'tache_parente_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function assigneA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_a_id');
    }
}
