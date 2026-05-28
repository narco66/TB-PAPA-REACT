<?php

namespace App\Models\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AuditSuiviRecommandation extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'audit_suivis_recommandations';

    public const ETATS = ['non_demarre', 'en_cours', 'realise', 'bloque', 'abandonne'];

    protected $fillable = [
        'recommandation_id', 'date_suivi', 'etat_avancement', 'pourcentage',
        'actions_realisees', 'actions_restantes', 'blocages', 'commentaire',
        'suivi_par_id',
    ];

    protected $casts = [
        'date_suivi' => 'date',
        'pourcentage' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function recommandation(): BelongsTo
    {
        return $this->belongsTo(AuditRecommandation::class, 'recommandation_id');
    }

    public function suiviPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suivi_par_id');
    }
}
