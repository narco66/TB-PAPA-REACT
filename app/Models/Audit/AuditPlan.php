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

class AuditPlan extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUTS = ['projet', 'soumis', 'valide', 'execute', 'cloture', 'archive'];

    protected $fillable = [
        'annee', 'libelle', 'description', 'orientation_strategique',
        'statut', 'date_debut', 'date_fin',
        'valide_par_id', 'valide_at', 'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'valide_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function missions(): HasMany
    {
        return $this->hasMany(AuditMission::class, 'plan_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
