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

class AuditConstat extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const GRAVITES = ['critique', 'majeur', 'moyen', 'mineur', 'observation'];

    public const NATURES = [
        'non_conformite', 'risque', 'inefficacite', 'inefficience',
        'controle_insuffisant', 'bonne_pratique', 'autre',
    ];

    protected $fillable = [
        'mission_id', 'code', 'libelle', 'description', 'preuves',
        'gravite', 'nature', 'cause_racine', 'impact', 'saisi_par_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(AuditMission::class, 'mission_id');
    }

    public function recommandations(): HasMany
    {
        return $this->hasMany(AuditRecommandation::class, 'constat_id');
    }

    public function saisiPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par_id');
    }
}
