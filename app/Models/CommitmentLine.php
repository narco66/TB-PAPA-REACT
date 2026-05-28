<?php

namespace App\Models;

use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetMouvement;
use App\Models\Budget\BudgetSourceFinancement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitmentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_mouvement_id', 'budget_ligne_id', 'libelle', 'montant',
        'montant_ceeac', 'montant_ptf', 'source_financement_id',
        'imputation_analytique', 'observations',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_ceeac' => 'decimal:2',
        'montant_ptf' => 'decimal:2',
    ];

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(BudgetMouvement::class, 'budget_mouvement_id');
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(BudgetLigne::class, 'budget_ligne_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(BudgetSourceFinancement::class, 'source_financement_id');
    }
}
