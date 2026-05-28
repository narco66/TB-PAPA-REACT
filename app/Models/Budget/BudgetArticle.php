<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetArticle extends Model
{
    protected $fillable = ['chapitre_id', 'exercice_id', 'code', 'libelle', 'statut', 'created_by', 'updated_by'];

    public function chapitre(): BelongsTo
    {
        return $this->belongsTo(BudgetChapitre::class, 'chapitre_id');
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(BudgetExercice::class, 'exercice_id');
    }

    public function paragraphes(): HasMany
    {
        return $this->hasMany(BudgetParagraphe::class, 'article_id');
    }
}
