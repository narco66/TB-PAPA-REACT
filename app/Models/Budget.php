<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Budget extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const SOURCE_CEEAC = 'ceeac';

    public const SOURCE_PARTENAIRE = 'partenaire';

    protected $fillable = [
        'budgetable_type',
        'budgetable_id',
        'papa_id',
        'source',
        'partenaire_id',
        'devise',
        'prevision',
        'engagement',
        'consommation',
        'reste_a_engager',
        'observations',
    ];

    protected $casts = [
        'prevision' => 'float',
        'engagement' => 'float',
        'consommation' => 'float',
        'reste_a_engager' => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function budgetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function papa(): BelongsTo
    {
        return $this->belongsTo(Papa::class);
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementBudgetaire::class);
    }

    public function tauxExecution(): float
    {
        if ($this->prevision <= 0) {
            return 0.0;
        }

        return round(($this->consommation / $this->prevision) * 100, 2);
    }

    public function recalculerResteAEngager(): void
    {
        $this->reste_a_engager = max(0, $this->prevision - $this->engagement);
    }
}
