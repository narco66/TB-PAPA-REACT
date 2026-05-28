<?php

namespace App\Models;

use App\Models\Budget\BudgetMouvement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_mouvement_id', 'montant_brut',
        'avance_deduite', 'retenue_garantie', 'retenue_fiscale', 'autres_retenues',
        'penalites_retard', 'autres_penalites',
        'montant_net_a_payer', 'justificatifs', 'observations',
        'liquide_par_id',
    ];

    protected $casts = [
        'montant_brut' => 'decimal:2',
        'avance_deduite' => 'decimal:2',
        'retenue_garantie' => 'decimal:2',
        'retenue_fiscale' => 'decimal:2',
        'autres_retenues' => 'decimal:2',
        'penalites_retard' => 'decimal:2',
        'autres_penalites' => 'decimal:2',
        'montant_net_a_payer' => 'decimal:2',
    ];

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(BudgetMouvement::class, 'budget_mouvement_id');
    }

    public function liquidePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liquide_par_id');
    }

    /** Calcule le montant net = brut - avance - retenues - pénalités. */
    public function calculerMontantNet(): float
    {
        return (float) $this->montant_brut
            - (float) $this->avance_deduite
            - (float) $this->retenue_garantie
            - (float) $this->retenue_fiscale
            - (float) $this->autres_retenues
            - (float) $this->penalites_retard
            - (float) $this->autres_penalites;
    }
}
