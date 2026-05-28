<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Alerte extends Model
{
    use HasFactory;

    public const NIVEAU_INFO = 'info';

    public const NIVEAU_ATTENTION = 'attention';

    public const NIVEAU_CRITIQUE = 'critique';

    protected $fillable = [
        'alertable_type',
        'alertable_id',
        'niveau',
        'categorie',
        'titre',
        'message',
        'contexte',
        'automatique',
        'statut',
        'assignee_a_id',
        'resolue_at',
        'resolue_par_id',
        'action_corrective',
    ];

    protected $casts = [
        'contexte' => 'array',
        'automatique' => 'boolean',
        'resolue_at' => 'datetime',
    ];

    public function alertable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assigneeA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_a_id');
    }

    public function resoluePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolue_par_id');
    }

    public function destinataires(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alerte_destinataires')
            ->withPivot(['notifie_at', 'lu_at'])
            ->withTimestamps();
    }

    public function scopeOuvertes(Builder $q): Builder
    {
        return $q->whereIn('statut', ['ouverte', 'en_traitement']);
    }

    public function scopeCritiques(Builder $q): Builder
    {
        return $q->where('niveau', self::NIVEAU_CRITIQUE);
    }
}
