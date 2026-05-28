<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Erreur structurée d'un import — stockée en table dédiée (relation HasMany sur BudgetImport)
 * pour permettre l'indexation, le filtrage et l'export riche XLSX/PDF.
 *
 * Le journal JSON sur BudgetImport est conservé en parallèle pour rétro-compatibilité.
 */
class BudgetImportErreur extends Model
{
    use HasFactory;

    protected $table = 'budget_import_erreurs';

    public const GRAVITES = ['critique', 'erreur', 'avertissement', 'info'];

    public const REGLES = [
        'colonne_manquante',
        'valeur_obligatoire',
        'format_invalide',
        'reference_inexistante',
        'doublon',
        'incoherence_hierarchique',
        'depassement_borne',
        'autre',
    ];

    public const STATUTS_TRAITEMENT = ['ouvert', 'corrige', 'ignore'];

    protected $fillable = [
        'import_id', 'feuille', 'ligne', 'colonne', 'valeur_fautive',
        'gravite', 'regle', 'message', 'correction_suggeree', 'statut_traitement',
    ];

    protected $casts = [
        'ligne' => 'integer',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(BudgetImport::class, 'import_id');
    }
}
