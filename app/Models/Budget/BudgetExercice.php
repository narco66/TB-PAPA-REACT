<?php

namespace App\Models\Budget;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BudgetExercice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUTS = [
        'brouillon', 'importe', 'controle', 'soumis', 'valide', 'rejete', 'cloture', 'archive',
    ];

    protected $fillable = [
        'annee', 'libelle', 'description', 'statut',
        'date_debut', 'date_fin', 'devise',
        'total_recettes', 'total_recettes_internes', 'total_recettes_externes',
        'total_depenses', 'total_depenses_ceeac_em', 'total_depenses_ptf',
        'total_fonctionnement', 'total_investissement', 'total_equipement',
        'valide_par_id', 'valide_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'annee' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'valide_at' => 'datetime',
        'total_recettes' => 'float',
        'total_recettes_internes' => 'float',
        'total_recettes_externes' => 'float',
        'total_depenses' => 'float',
        'total_depenses_ceeac_em' => 'float',
        'total_depenses_ptf' => 'float',
        'total_fonctionnement' => 'float',
        'total_investissement' => 'float',
        'total_equipement' => 'float',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['annee', 'libelle', 'statut', 'total_depenses', 'total_recettes'])
            ->logOnlyDirty();
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(BudgetLigne::class, 'exercice_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(BudgetImport::class, 'exercice_id');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function estVerrouille(): bool
    {
        return in_array($this->statut, ['cloture', 'archive'], true);
    }

    public function recalculerTotaux(): void
    {
        $racines = $this->lignes()->whereNull('parent_id')->get();

        $this->total_recettes = (float) $this->lignes()->where('nature', 'recette')->whereNull('parent_id')->sum('montant_total');
        $this->total_recettes_internes = (float) $this->lignes()->where('type_budget', 'recette_interne')->whereNull('parent_id')->sum('montant_total');
        $this->total_recettes_externes = (float) $this->lignes()->where('type_budget', 'recette_externe')->whereNull('parent_id')->sum('montant_total');

        $this->total_depenses = (float) $this->lignes()->where('nature', 'depense')->whereNull('parent_id')->sum('montant_total');
        $this->total_depenses_ceeac_em = (float) $this->lignes()->where('nature', 'depense')->whereNull('parent_id')->sum('montant_ceeac_em');
        $this->total_depenses_ptf = (float) $this->lignes()->where('nature', 'depense')->whereNull('parent_id')->sum('montant_ptf');

        $this->total_fonctionnement = (float) $this->lignes()->where('type_budget', 'fonctionnement')->sum('montant_total');
        $this->total_investissement = (float) $this->lignes()->where('type_budget', 'investissement')->sum('montant_total');
        $this->total_equipement = (float) $this->lignes()->where('type_budget', 'equipement')->sum('montant_total');

        $this->saveQuietly();
    }
}
