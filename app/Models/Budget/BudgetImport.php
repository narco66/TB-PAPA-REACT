<?php

namespace App\Models\Budget;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetImport extends Model
{
    use HasFactory;

    public const STATUTS = [
        'brouillon',
        'analyse',
        'valide',
        'importe',
        'echec',
        'annule',
        'a_corriger',
        // Statuts historiques conservés pour rétro-compatibilité.
        'prevu',
        'en_cours',
        'reussi',
        'rollback',
    ];

    public const TYPES_DONNEES = [
        'budget',
        'axes',
        'produits',
        'sous_produits',
        'activites',
        'taches',
        'indicateurs',
    ];

    protected $fillable = [
        'exercice_id', 'parent_import_id', 'feuille_source', 'type_donnees',
        'fichier_nom', 'chemin_stockage', 'taille_octets', 'hash_sha256',
        'statut', 'nb_lignes_lues', 'nb_lignes_creees', 'nb_lignes_mises_a_jour',
        'nb_erreurs', 'journal', 'options',
        'execute_par_id', 'execute_at',
    ];

    protected $casts = [
        'journal' => 'array',
        'options' => 'array',
        'execute_at' => 'datetime',
        'taille_octets' => 'integer',
    ];

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(BudgetExercice::class, 'exercice_id');
    }

    public function executePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'execute_par_id');
    }

    /** Import parent (en cas de fichier multi-feuilles). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_import_id');
    }

    /** Imports enfants (un par feuille). */
    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_import_id');
    }

    /** Erreurs structurées de cet import. */
    public function erreurs(): HasMany
    {
        return $this->hasMany(BudgetImportErreur::class, 'import_id');
    }

    /** Détermine si l'import a une parent (= il est une feuille enfant). */
    public function estFeuille(): bool
    {
        return $this->parent_import_id !== null;
    }

    /** Détermine si l'import est multi-feuilles (a des enfants). */
    public function estMultiFeuilles(): bool
    {
        return $this->enfants()->exists();
    }
}
