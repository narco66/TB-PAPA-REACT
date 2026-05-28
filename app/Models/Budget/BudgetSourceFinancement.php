<?php

namespace App\Models\Budget;

use App\Models\Partenaire;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetSourceFinancement extends Model
{
    use HasFactory;

    protected $table = 'budget_sources_financement';

    protected $fillable = [
        'code', 'libelle', 'type', 'categorie',
        'partenaire_id', 'description', 'actif',
    ];

    protected $casts = ['actif' => 'boolean'];

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(BudgetLigne::class, 'source_financement_id');
    }

    public function estInterne(): bool
    {
        return $this->type === 'interne';
    }
}
