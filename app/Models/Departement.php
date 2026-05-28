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

    protected $fillable = ['code', 'libelle', 'description', 'commissaire_id', 'ordre', 'actif'];

    protected $casts = ['actif' => 'boolean'];

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
}
