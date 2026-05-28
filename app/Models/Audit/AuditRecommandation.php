<?php

namespace App\Models\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AuditRecommandation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const PRIORITES = ['urgente', 'haute', 'moyenne', 'basse'];

    public const STATUTS = [
        'ouverte', 'planifiee', 'en_cours', 'mise_en_oeuvre',
        'verifiee', 'rejetee', 'abandonnee',
    ];

    protected $fillable = [
        'constat_id', 'code', 'libelle', 'action_proposee',
        'priorite', 'responsable_mise_en_oeuvre_id', 'date_echeance',
        'statut', 'pourcentage_avancement', 'saisi_par_id',
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'pourcentage_avancement' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function constat(): BelongsTo
    {
        return $this->belongsTo(AuditConstat::class, 'constat_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_mise_en_oeuvre_id');
    }

    public function saisiPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par_id');
    }

    public function suivis(): HasMany
    {
        return $this->hasMany(AuditSuiviRecommandation::class, 'recommandation_id');
    }

    /** True si la date d'échéance est dépassée et la recommandation n'est pas vérifiée. */
    public function estEnRetard(): bool
    {
        return $this->date_echeance
            && $this->date_echeance->isPast()
            && ! in_array($this->statut, ['verifiee', 'rejetee', 'abandonnee'], true);
    }
}
