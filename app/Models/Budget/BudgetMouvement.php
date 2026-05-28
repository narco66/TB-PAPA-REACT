<?php

namespace App\Models\Budget;

use App\Models\Partenaire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Mouvement budgétaire IPSAS — chaque opération du cycle d'exécution
 * (Engagement → Liquidation → Ordonnancement → Paiement) est enregistrée ici.
 *
 * Les mouvements sont chaînés via `parent_mouvement_id` pour assurer la traçabilité
 * complète et la séparation ordonnateur / comptable (COSO ERM).
 */
class BudgetMouvement extends Model
{
    use HasFactory, LogsActivity;

    public const TYPES = [
        'engagement', 'liquidation', 'ordonnancement', 'paiement',
        'desengagement', 'ajustement', 'transfert',
    ];

    /** Ordre canonique du cycle IPSAS pour chaînage. */
    public const ORDRE_CYCLE = ['engagement', 'liquidation', 'ordonnancement', 'paiement'];

    public const MODES_PAIEMENT = ['virement', 'cheque', 'especes', 'mobile_money', 'autre'];

    public const STATUTS = ['en_attente', 'valide', 'rejete', 'annule'];

    protected $fillable = [
        'ligne_id', 'parent_mouvement_id', 'type', 'montant', 'date_mouvement',
        'reference', 'numero_piece', 'motif',
        'beneficiaire_nom', 'beneficiaire_reference', 'partenaire_id', 'supplier_id',
        'ordonnateur_id', 'comptable_id',
        'mode_paiement', 'compte_bancaire', 'date_valeur',
        'statut_mouvement', 'piece_justificative',
        'saisi_par_id', 'valide_at', 'valide_par_id',
        'expense_request_id', 'type_engagement',
    ];

    protected $casts = [
        'montant' => 'float',
        'date_mouvement' => 'date',
        'date_valeur' => 'date',
        'valide_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(BudgetLigne::class, 'ligne_id');
    }

    /** Mouvement parent dans la chaîne IPSAS. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_mouvement_id');
    }

    /** Mouvements suivants dans la chaîne IPSAS. */
    public function suivants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_mouvement_id');
    }

    public function saisiPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function ordonnateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordonnateur_id');
    }

    public function comptable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comptable_id');
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function expenseRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ExpenseRequest::class, 'expense_request_id');
    }

    public function commitmentLines(): HasMany
    {
        return $this->hasMany(\App\Models\CommitmentLine::class, 'budget_mouvement_id');
    }

    /** Remonte la chaîne IPSAS depuis ce mouvement jusqu'à l'engagement initial. */
    public function chaineCompleter(): Collection
    {
        $chaine = collect([$this]);
        $courant = $this;
        while ($courant->parent_mouvement_id) {
            $courant = $courant->parent;
            if (! $courant) {
                break;
            }
            $chaine->prepend($courant);
        }

        return $chaine;
    }

    /** True si ce mouvement peut encore avancer dans le cycle. */
    public function aTransitionSuivante(): bool
    {
        $idx = array_search($this->type, self::ORDRE_CYCLE, true);

        return $idx !== false && $idx < count(self::ORDRE_CYCLE) - 1;
    }

    /** Type du prochain mouvement attendu dans le cycle. */
    public function typeSuivant(): ?string
    {
        $idx = array_search($this->type, self::ORDRE_CYCLE, true);
        if ($idx === false || $idx >= count(self::ORDRE_CYCLE) - 1) {
            return null;
        }

        return self::ORDRE_CYCLE[$idx + 1];
    }
}
