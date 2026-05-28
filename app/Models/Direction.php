<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Direction extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['code', 'libelle', 'type', 'departement_id', 'directeur_id', 'description', 'actif'];

    protected $casts = ['actif' => 'boolean'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function directeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'directeur_id');
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function actionsPrioritaires(): HasMany
    {
        return $this->hasMany(ActionPrioritaire::class);
    }

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class);
    }

    public function estTechnique(): bool
    {
        return $this->type === 'technique';
    }

    public function estAppui(): bool
    {
        return $this->type === 'appui_soutien';
    }
}
