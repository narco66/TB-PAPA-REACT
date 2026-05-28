<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrganizationalUnit extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = [
        'commission', 'presidence', 'vice_presidence', 'secretariat_general',
        'departement', 'direction', 'service', 'bureau', 'cellule',
    ];

    protected $fillable = [
        'uuid', 'code', 'libelle', 'type',
        'parent_id', 'departement_id', 'direction_id', 'service_id',
        'responsable_id', 'description', 'ordre', 'niveau', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
        'niveau' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrganizationalUnit $ou) {
            if (empty($ou->uuid)) {
                $ou->uuid = (string) Str::uuid();
            }
            $ou->niveau = $ou->parent_id
                ? (static::find($ou->parent_id)?->niveau ?? 0) + 1
                : 0;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordre');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function ancetres(): Collection
    {
        $chain = collect([$this]);
        $current = $this;
        while ($current->parent_id) {
            $current = $current->parent;
            if (! $current) {
                break;
            }
            $chain->prepend($current);
        }

        return $chain;
    }

    public function getCheminHierarchiqueAttribute(): string
    {
        return $this->ancetres()->pluck('libelle')->implode(' > ');
    }

    public function scopeActif(Builder $q): Builder
    {
        return $q->where('actif', true);
    }

    public function scopeRacines(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    public function scopeType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }
}
