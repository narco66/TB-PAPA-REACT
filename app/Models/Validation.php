<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Validation extends Model
{
    use HasFactory;

    protected $fillable = [
        'validable_type',
        'validable_id',
        'etape',
        'decision',
        'demandeur_id',
        'valideur_id',
        'decide_at',
        'commentaire',
        'donnees_avant',
        'donnees_apres',
    ];

    protected $casts = [
        'decide_at' => 'datetime',
        'donnees_avant' => 'array',
        'donnees_apres' => 'array',
    ];

    public function validable(): MorphTo
    {
        return $this->morphTo();
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valideur_id');
    }
}
