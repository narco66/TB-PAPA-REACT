<?php

namespace App\Models\Audit;

use App\Models\Departement;
use App\Models\Direction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AuditMission extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = [
        'financier', 'conformite', 'performance', 'systeme_information',
        'organisationnel', 'thematique', 'suivi',
    ];

    public const STATUTS = [
        'planifiee', 'lettre_emise', 'en_cours', 'projet_rapport',
        'rapport_definitif', 'cloturee', 'annulee',
    ];

    public const PRIORITES = ['critique', 'haute', 'moyenne', 'basse'];

    protected $fillable = [
        'plan_id', 'code', 'titre', 'objectifs', 'perimetre',
        'type', 'priorite', 'statut',
        'date_debut_prevue', 'date_fin_prevue',
        'date_debut_reelle', 'date_fin_reelle',
        'chef_mission_id', 'departement_audite_id', 'direction_auditee_id',
        'lettre_mission', 'synthese', 'created_by',
    ];

    protected $casts = [
        'date_debut_prevue' => 'date',
        'date_fin_prevue' => 'date',
        'date_debut_reelle' => 'date',
        'date_fin_reelle' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(AuditPlan::class, 'plan_id');
    }

    public function chefMission(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_mission_id');
    }

    public function departementAudite(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_audite_id');
    }

    public function directionAuditee(): BelongsTo
    {
        return $this->belongsTo(Direction::class, 'direction_auditee_id');
    }

    public function equipe(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audit_mission_equipe', 'mission_id', 'user_id')
            ->withPivot('role_mission')
            ->withTimestamps();
    }

    public function constats(): HasMany
    {
        return $this->hasMany(AuditConstat::class, 'mission_id');
    }

    public function recommandations(): HasManyThrough
    {
        return $this->hasManyThrough(
            AuditRecommandation::class,
            AuditConstat::class,
            'mission_id',
            'constat_id',
        );
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
