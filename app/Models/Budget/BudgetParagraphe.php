<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetParagraphe extends Model
{
    protected $fillable = ['article_id', 'exercice_id', 'code', 'libelle', 'statut', 'created_by', 'updated_by'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(BudgetArticle::class, 'article_id');
    }

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(BudgetExercice::class, 'exercice_id');
    }
}
