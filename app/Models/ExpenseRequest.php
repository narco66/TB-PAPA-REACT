<?php

namespace App\Models;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetMouvement;
use App\Models\Budget\BudgetSourceFinancement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ExpenseRequest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * 18 types d'engagements alignés sur la nomenclature CEEAC.
     */
    public const TYPES_ENGAGEMENT = [
        'achat_biens' => 'Achat de biens',
        'prestation_services' => 'Prestation de services',
        'travaux' => 'Travaux',
        'mission_officielle' => 'Mission officielle',
        'formation_atelier' => 'Formation / atelier / séminaire',
        'contrat_convention' => 'Contrat ou convention',
        'subvention' => 'Subvention',
        'appui_institutionnel' => 'Appui institutionnel',
        'fonctionnement' => 'Dépenses de fonctionnement',
        'investissement' => 'Dépenses d\'investissement',
        'frais_personnel' => 'Frais de personnel',
        'regularisation' => 'Engagement de régularisation',
        'complementaire' => 'Engagement complémentaire',
        'modificatif' => 'Engagement modificatif',
        'pluriannuel' => 'Engagement pluriannuel',
        'financement_ceeac' => 'Financement CEEAC',
        'financement_ptf' => 'Financement PTF',
        'financement_mixte' => 'Financement mixte CEEAC/PTF',
    ];

    public const STATUTS = [
        'brouillon', 'soumis', 'en_validation_hierarchique', 'retourne_correction',
        'rejete', 'valide', 'engage', 'annule',
    ];

    protected $fillable = [
        'numero', 'exercice_id', 'demandeur_id', 'departement_id', 'direction_id',
        'activite_id', 'tache_id', 'type_engagement', 'objet', 'justification', 'description_detaillee',
        'montant_estime', 'montant_estime_ceeac', 'montant_estime_ptf', 'devise',
        'source_financement_id', 'supplier_pressenti_id',
        'date_besoin_prevu', 'date_livraison_souhaitee',
        'statut', 'valideur_hierarchique_id', 'valide_at', 'motif_decision',
        'budget_mouvement_engagement_id', 'priorite',
    ];

    protected $casts = [
        'montant_estime' => 'decimal:2',
        'montant_estime_ceeac' => 'decimal:2',
        'montant_estime_ptf' => 'decimal:2',
        'date_besoin_prevu' => 'date',
        'date_livraison_souhaitee' => 'date',
        'valide_at' => 'datetime',
        'priorite' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(BudgetExercice::class, 'exercice_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function sourceFinancement(): BelongsTo
    {
        return $this->belongsTo(BudgetSourceFinancement::class, 'source_financement_id');
    }

    public function supplierPressenti(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_pressenti_id');
    }

    public function valideurHierarchique(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valideur_hierarchique_id');
    }

    public function engagement(): BelongsTo
    {
        return $this->belongsTo(BudgetMouvement::class, 'budget_mouvement_engagement_id');
    }

    /** Génère un numéro institutionnel EB-YYYY-NNNNN unique. */
    public static function genererNumero(int $exerciceAnnee): string
    {
        $prefix = 'EB-' . $exerciceAnnee . '-';
        $dernier = static::where('numero', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $dernier ? ((int) substr($dernier->numero, -5)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
