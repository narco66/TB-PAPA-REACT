<?php

namespace App\Models\Budget;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mapping persisté entre les en-têtes Excel et les champs canoniques de l'application.
 * Permet à un utilisateur de réutiliser le même schéma d'import d'un fichier à l'autre.
 */
class BudgetImportMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type_donnees', 'libelle', 'mapping', 'partage',
    ];

    protected $casts = [
        'mapping' => 'array',
        'partage' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
