<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Service extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUTS = ['actif', 'suspendu', 'archive'];

    protected $fillable = [
        'uuid', 'code', 'libelle', 'description',
        'direction_id', 'departement_id', 'chef_service_id',
        'ordre', 'actif', 'statut',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Service $service) {
            if (empty($service->uuid)) {
                $service->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            // Auto-fill departement_id à partir de la direction (cohérence hiérarchique)
            if ($service->direction_id && ! $service->departement_id) {
                $direction = Direction::find($service->direction_id);
                if ($direction && $direction->departement_id) {
                    $service->departement_id = $direction->departement_id;
                }
            }
            if (empty($service->statut)) {
                $service->statut = $service->actif ? 'actif' : 'suspendu';
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    // === Relations hiérarchiques ===

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function chefService(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_service_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // === Scopes ===

    public function scopeActif(Builder $q): Builder
    {
        return $q->where('actif', true)->where('statut', 'actif');
    }

    /** Chemin hiérarchique complet : "Département > Direction > Service" */
    public function getCheminHierarchiqueAttribute(): string
    {
        $parts = array_filter([
            $this->departement?->libelle,
            $this->direction?->libelle,
            $this->libelle,
        ]);

        return implode(' > ', $parts);
    }
}
