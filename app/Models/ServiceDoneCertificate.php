<?php

namespace App\Models;

use App\Models\Budget\BudgetMouvement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceDoneCertificate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const CONFORMITES = ['conforme', 'partiellement_conforme', 'non_conforme'];

    public const STATUTS = ['projet', 'valide', 'rejete'];

    protected $fillable = [
        'budget_mouvement_id', 'reference', 'date_constatation', 'description',
        'montant_constate', 'conformite_qualitative', 'conformite_quantitative',
        'observations', 'constate_par_id', 'valide_par_id', 'valide_at',
        'statut', 'motif_rejet',
    ];

    protected $casts = [
        'date_constatation' => 'date',
        'montant_constate' => 'decimal:2',
        'valide_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function mouvement(): BelongsTo
    {
        return $this->belongsTo(BudgetMouvement::class, 'budget_mouvement_id');
    }

    public function constatePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'constate_par_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public static function genererReference(int $annee): string
    {
        $prefix = "SF-{$annee}-";
        $dernier = static::where('reference', 'like', $prefix . '%')->orderByDesc('id')->first();
        $seq = $dernier ? ((int) substr($dernier->reference, -5)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
