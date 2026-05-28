<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Departement extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = ['presidence', 'vice_presidence', 'secretariat_general', 'departement_technique', 'organe_consultatif'];

    protected $fillable = ['uuid', 'code', 'libelle', 'description', 'type', 'commissaire_id', 'ordre', 'actif'];

    protected $casts = ['actif' => 'boolean', 'ordre' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (Departement $d) {
            if (empty($d->uuid)) {
                $d->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function commissaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commissaire_id');
    }

    public function directions(): HasMany
    {
        return $this->hasMany(Direction::class);
    }

    public function axes(): HasMany
    {
        return $this->hasMany(Axe::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
