<?php

namespace App\Models\Budget;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetChapitre extends Model
{
    protected $fillable = ['exercice_id', 'code', 'libelle', 'statut', 'created_by', 'updated_by'];

    public function exercice(): BelongsTo
    {
        return $this->belongsTo(BudgetExercice::class, 'exercice_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(BudgetArticle::class, 'chapitre_id');
    }
}
