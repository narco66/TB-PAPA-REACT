<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MouvementBudgetaire extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'mouvements_budgetaires';

    protected $fillable = [
        'budget_id',
        'type',
        'montant',
        'date_mouvement',
        'reference',
        'motif',
        'saisi_par_id',
        'valide_at',
        'valide_par_id',
    ];

    protected $casts = [
        'montant' => 'float',
        'date_mouvement' => 'date',
        'valide_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
